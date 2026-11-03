<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Auth;

use App\Modules\User\Enums\UserRole;
use App\Modules\User\Models\User;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pcntl')]
final class AdminUserConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    public function test_parallel_demotions_cannot_remove_every_admin(): void
    {
        $first = User::factory()->create(attributes: ['role' => UserRole::Admin]);
        $second = User::factory()->create(attributes: ['role' => UserRole::Admin]);
        $firstToken = Auth::guard('api')->attempt(['email' => $first->email, 'password' => 'password']);
        $secondToken = Auth::guard('api')->attempt(['email' => $second->email, 'password' => 'password']);
        self::assertIsString($firstToken);
        self::assertIsString($secondToken);
        Auth::forgetGuards();

        $firstResult = tempnam(directory: sys_get_temp_dir(), prefix: 'admin-role-');
        $secondResult = tempnam(directory: sys_get_temp_dir(), prefix: 'admin-role-');
        self::assertIsString($firstResult);
        self::assertIsString($secondResult);

        $children = [
            $this->forkRoleChange($firstToken, $second->id, $firstResult),
            $this->forkRoleChange($secondToken, $first->id, $secondResult),
        ];

        foreach ($children as [, $signal]) {
            fwrite(stream: $signal, data: '1');
            fclose(stream: $signal);
        }

        foreach ($children as [$pid]) {
            pcntl_waitpid(process_id: $pid, status: $status);
            self::assertTrue(pcntl_wifexited(status: $status));
            self::assertSame(0, pcntl_wexitstatus(status: $status));
        }

        $statuses = [(int) file_get_contents(filename: $firstResult), (int) file_get_contents(filename: $secondResult)];
        unlink(filename: $firstResult);
        unlink(filename: $secondResult);

        sort(array: $statuses);
        self::assertSame([200, 409], $statuses);
        self::assertSame(1, User::query()->where(column: 'role', operator: UserRole::Admin)->count());
    }

    /**
     * @return array{int, resource}
     */
    private function forkRoleChange(string $token, int $targetId, string $resultFile): array
    {
        $sockets = stream_socket_pair(domain: STREAM_PF_UNIX, type: STREAM_SOCK_STREAM, protocol: STREAM_IPPROTO_IP);
        self::assertIsArray($sockets);

        $pid = pcntl_fork();
        self::assertNotSame(-1, $pid);

        if ($pid === 0) {
            fclose(stream: $sockets[0]);
            fread(stream: $sockets[1], length: 1);
            fclose(stream: $sockets[1]);

            DB::disconnect();
            Auth::forgetGuards();

            $request = Request::create(
                uri: "/api/admin/users/{$targetId}/role",
                method: 'PATCH',
                parameters: ['role' => UserRole::Customer->value],
                server: [
                    'HTTP_ACCEPT' => 'application/json',
                    'HTTP_ACCEPT_LANGUAGE' => 'en',
                    'HTTP_AUTHORIZATION' => "Bearer {$token}",
                ],
            );
            $response = resolve(name: Kernel::class)->handle($request);
            file_put_contents(filename: $resultFile, data: (string) $response->getStatusCode());
            exit(0);
        }

        fclose(stream: $sockets[1]);

        return [$pid, $sockets[0]];
    }
}

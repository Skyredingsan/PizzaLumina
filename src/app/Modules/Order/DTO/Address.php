<?php

declare(strict_types=1);

namespace App\Modules\Order\DTO;

use InvalidArgumentException;
use Stringable;

final readonly class Address implements Stringable
{
    public function __construct(
        public string $region,
        public string $city,
        public string $street,
        public string $building,
        public ?string $entrance = null,
        public ?string $apartment = null,
        public ?string $zip = null,
    ) {
        foreach (['region', 'city', 'street', 'building'] as $field) {
            if (trim(string: $this->$field) === '') {
                throw new InvalidArgumentException(
                    message: "Address field '{$field}' cannot be empty."
                );
            }
        }

        if (mb_strlen(string: $this->region) > 100) {
            throw new InvalidArgumentException(message: 'Address.region is too long (max 100).');
        }
        if (mb_strlen(string: $this->city) > 100) {
            throw new InvalidArgumentException(message: 'Address.city is too long (max 100).');
        }
        if (mb_strlen(string: $this->street) > 200) {
            throw new InvalidArgumentException(message: 'Address.street is too long (max 200).');
        }
        if (mb_strlen(string: $this->building) > 20) {
            throw new InvalidArgumentException(message: 'Address.building is too long (max 20).');
        }
        if ($this->entrance !== null && mb_strlen(string: $this->entrance) > 10) {
            throw new InvalidArgumentException(message: 'Address.entrance is too long (max 10).');
        }
        if ($this->apartment !== null && mb_strlen(string: $this->apartment) > 20) {
            throw new InvalidArgumentException(message: 'Address.apartment is too long (max 20).');
        }
        if ($this->zip !== null && ! preg_match(pattern: '/^[A-Z0-9\- ]{3,12}$/i', subject: $this->zip)) {
            throw new InvalidArgumentException(
                message: 'Address.zip must be 3-12 chars of letters/digits/space/hyphen. Got: ' . $this->zip
            );
        }
    }

    /**
     * @param  array{region: string, city: string, street: string, building: string, entrance?: string|null, apartment?: string|null, zip?: string|null}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            region: $data['region'],
            city: $data['city'],
            street: $data['street'],
            building: $data['building'],
            entrance: $data['entrance'] ?? null,
            apartment: $data['apartment'] ?? null,
            zip: $data['zip'] ?? null,
        );
    }

    public function __toString(): string
    {
        $parts = [
            $this->region,
            $this->city,
            $this->street,
            'д. ' . $this->building,
        ];
        if ($this->apartment !== null) {
            $parts[] = 'кв. ' . $this->apartment;
        }
        if ($this->entrance !== null) {
            $parts[] = 'под. ' . $this->entrance;
        }
        if ($this->zip !== null) {
            $parts[] = $this->zip;
        }

        return implode(separator: ', ', array: $parts);
    }
    public function equals(self $other): bool
    {
        return $this->region === $other->region
            && $this->city === $other->city
            && $this->street === $other->street
            && $this->building === $other->building
            && $this->entrance === $other->entrance
            && $this->apartment === $other->apartment
            && $this->zip === $other->zip;
    }
}

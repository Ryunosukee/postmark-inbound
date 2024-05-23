<?php

namespace dcorreah\Postmark\Support;

use DateTime;
use Exception;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * This file is part of the Postmark Inbound package.
 *
 * @property-read string $timezone
 * @property-read bool $isUtc
 */
class PostmarkDate extends DateTime
{
    /**
     *  Constants for numeric representation of the day of the week.
     */
    const SUNDAY = 0;
    const MONDAY = 1;
    const TUESDAY = 2;
    const WEDNESDAY = 3;
    const THURSDAY = 4;
    const FRIDAY = 5;
    const SATURDAY = 6;

    /**
     * Textual representation of a day, three letters.
     *
     * @var array
     */
    protected static $days_abbreviated = [
        self::SUNDAY => 'Sun',
        self::MONDAY => 'Mon',
        self::TUESDAY => 'Tue',
        self::WEDNESDAY => 'Wed',
        self::THURSDAY => 'Thu',
        self::FRIDAY => 'Fri',
        self::SATURDAY => 'Sat',
    ];

    /**
     * Create a new instance from a string.
     *
     * @param string $date
     * @return static
     */
    public static function parse($date)
    {
        try {
            return new static(
                collect(explode(' ', $date))
                    ->reject(function ($value) {
                        return self::getAbbreviatedDaysCollection()->contains(trim($value, ','));
                    })
                    ->take(5)
                    ->implode(' ')
            );
        } catch (Exception $e) {
            //
        }

        return new static();
    }

    /**
     * Get a collection of the textual respresentation of a day as three letters.
     *
     * @return Collection
     */
    public static function getAbbreviatedDaysCollection()
    {
        return collect(static::getAbbreviatedDays());
    }

    /**
     * Get the textual respresentation of a day as three letters.
     *
     * @return array
     */
    public static function getAbbreviatedDays()
    {
        return static::$days_abbreviated;
    }

    /**
     * Set the instance to UTC timezone.
     *
     * @return static
     */
    public function inUtcTimezone()
    {
        return parent::setTimezone(timezone_open('UTC'));
    }

    /**
     * Dynamically retrieve attributes on the data model.
     *
     * @param string $key
     * @return bool|string
     * @throws InvalidArgumentException
     */
    public function __get($key)
    {
        return match (true) {
            $key === 'isUtc' => $this->getOffset() === 0,
            $key === 'timezone' => $this->getTimezone()->getName(),
            default => throw new InvalidArgumentException(sprintf("Unknown getter '%s'", $key)),
        };
    }
}

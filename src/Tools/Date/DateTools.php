<?php


namespace App\Tools\Date;

//TODO make a service
class DateTools
{
    private const REGEX_DATE_ATOM = "/\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2]\d|3[0-1])T[0-2]\d:[0-5]\d:[0-5]\d([+-][0-2]\d:[0-5]\d|Z)/";

    public static function getNow($toSql=false)
    {
        $date = new \DateTime(); //Server is UTC, it's 14h30 (Europe/Paris)
        // $date->format("Y-m-d H:i:s");   // output is xxxx-xx-xx 13:30:00
        $date->setTimezone(DateTools::getTimeZone());
        // $date->format("Y-m-d H:i:s");   // output is xxxx-xx-xx 14:30:00

        if($toSql===true){
            return $date->format('Y-m-d H:i:s');
        }


        return $date;
    }

    public static function getTimeZone()
    {
        return new \DateTimeZone("Europe/Paris");
    }

    /**
     * @param $string
     * @return \DateTime|false
     */
    public static function parseIsoAtomString($string)
    {
        if(!preg_match(self::REGEX_DATE_ATOM, $string)){
            throw new \RuntimeException('date format should be ISO ATOM (Y-m-d\TH:i:sP) ex : 2017-12-31T23:59:59+09:00 ');
        }
        $date = \DateTime::createFromFormat(\DateTime::ATOM, $string, new \DateTimeZone("UTC"));
        $date->setTimezone(new \DateTimeZone($_ENV["TIMEZONE"]));

        return $date;
    }

    public static function dateTimeToDayDateTimes(\DateTime $dateTime)
    {
        $s = clone($dateTime);
        $e = clone($dateTime);
        $s->setTime(0,0,0);
        $e->setTime(23,59,59);
        return [$s, $e];
    }

    public static function toSQLString(\DateTimeInterface $dateTime, $asDate = false)
    {
        if($asDate) {
            return $dateTime->format('Y-m-d');
        }
        return $dateTime->format('Y-m-d H:i:s');
    }

    /**
     * @deprecated Should use toSQLString
     * @param \DateTime $dateTime
     * @return string
     */
    public static function toSQLDateString(\DateTime $dateTime)
    {
        return $dateTime->format('Y-m-d H:i:s');
    }

    public static function parseSQLDateTime($string)
    {
        return \DateTime::createFromFormat('Y-m-d H:i:s', $string);
    }

    public static function databaseToFormattedString($date) {
        return date('d/m/Y H:i:s', strtotime($date));
    }
}
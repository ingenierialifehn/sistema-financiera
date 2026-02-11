<?php
class TextHelper
{
    private static $UNIDADES = [
        '',
        'UN ',
        'DOS ',
        'TRES ',
        'CUATRO ',
        'CINCO ',
        'SEIS ',
        'SIETE ',
        'OCHO ',
        'NUEVE ',
        'DIEZ ',
        'ONCE ',
        'DOCE ',
        'TRECE ',
        'CATORCE ',
        'QUINCE ',
        'DIECISEIS ',
        'DIECISIETE ',
        'DIECIOCHO ',
        'DIECINUEVE ',
        'VEINTE '
    ];

    private static $DECENAS = [
        'VENTI',
        'TREINTA ',
        'CUARENTA ',
        'CINCUENTA ',
        'SESENTA ',
        'SETENTA ',
        'OCHENTA ',
        'NOVENTA ',
        'CIEN '
    ];

    private static $CENTENAS = [
        'CIENTO ',
        'DOSCIENTOS ',
        'TRESCIENTOS ',
        'CUATROCIENTOS ',
        'QUINIENTOS ',
        'SEISCIENTOS ',
        'SETECIENTOS ',
        'OCHOCIENTOS ',
        'NOVECIENTOS '
    ];

    public static function numToLetras($number)
    {
        $converted = '';
        $number = number_format($number, 2, '.', '');
        $splitNum = explode('.', $number);
        $fraction = $splitNum[1];
        $intPart = $splitNum[0];

        if ($intPart == 0) {
            $converted = 'CERO ';
        } else {
            $converted = self::convertGroup($intPart);
        }

        $converted .= ' LEMPIRAS CON ' . $fraction . '/100 CENTAVOS';

        return trim($converted);
    }

    private static function convertGroup($n)
    {
        $output = '';

        if ($n == '100') {
            return 'CIEN ';
        } elseif ($n > 100 && $n <= 999) {
            $output = self::$CENTENAS[intval($n / 100) - 1];
            $n = $n % 100;
        }

        if ($n <= 20) {
            $output .= self::$UNIDADES[$n];
        } elseif ($n > 20 && $n <= 29) {
            $output .= 'VEINTI' . self::$UNIDADES[$n - 20];
        } elseif ($n >= 30 && $n <= 99) {
            $output .= self::$DECENAS[intval($n / 10) - 2];
            if (($n % 10) != 0) {
                $output .= 'Y ' . self::$UNIDADES[$n % 10];
            }
        } elseif ($n >= 1000 && $n <= 999999) {
            $output .= self::convertGroup(intval($n / 1000)) . 'MIL ';
            if (($n % 1000) != 0) {
                $output .= self::convertGroup($n % 1000);
            }
        } elseif ($n >= 1000000 && $n <= 999999999) {
            $output .= self::convertGroup(intval($n / 1000000)) . 'MILLONES ';
            if (($n % 1000000) != 0) {
                $output .= self::convertGroup($n % 1000000);
            }
        }

        return $output;
    }
}
?>
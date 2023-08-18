<?php

namespace Richen\Engine;

class Filter
{
    public static $log;
    public static $logEx;

    private string $message;

    private static string $LT_P = 'пПnPp', $LT_I = 'иИiI1u', $LT_E = 'еЕeE', $LT_D = 'дДdD', $LT_Z = 'зЗ3zZ3',
        $LT_M = 'мМmM', $LT_U = 'уУyYuU',$LT_O = 'оОoO0', $LT_L = 'лЛlL', $LT_S = 'сСcCsS', $LT_A = 'аАaA',
        $LT_N = 'нНhH', $LT_G = 'гГgG', $LT_CH = 'чЧ4', $LT_K = 'кКkK', $LT_C = 'цЦcC', $LT_R = 'рРpPrR',
        $LT_H = 'хХxXhH', $LT_YI = 'йЙy', $LT_YA = 'яЯ', $LT_YO = 'ёЁ', $LT_YU = 'юЮ', $LT_B = 'бБ6bB',
        $LT_T = 'тТtT', $LT_HS = 'ъЪ', $LT_SS = 'ьЬ', $LT_Y = 'ыЫ';

    public static $exceptions = array(
        'команд', 'рубл', 'премь', 'оскорб', 'краснояр', 'бояр', 'ноябр', 'карьер', 
        'мандат', 'употр', 'плох', 'интер', 'веер', 'фаер', 'феер', 'hyundai', 'тату',
        'браконь', 'roup', 'сараф', 'держ', 'слаб', 'ридер', 'истреб', 'потреб', 'коридор', 
        'sound', 'дерг', 'подоб', 'бичен', 'ричен', 'коррид', 'дубл', 'курьер', 'экст', 'try', 'enter', 
        'oun', 'aube', 'ibarg', '16', 'kres', 'глуб', 'ebay', 'eeb', 'shuy', 'ансам', 'cayenne', 
        'ain', 'oin', 'тряс', 'ubu', 'uen', 'uip', 'oup', 'кораб', 'боеп', 'деепр', 'хульс',
        'een', 'ee6', 'ein', 'сугуб', 'карб', 'гроб', 'лить', 'рсук', 'влюб', 'хулио', 'ляп', 
        'граб', 'ибог', 'вело', 'ебэ', 'перв', 'eep', 'ying', 'laun', 'чаепитие', 
    );
    public static function getFiltered(string $message): array { $chatfilter = self::getFilteredText($message); return ['message' => $chatfilter, 'isallowed' => ($chatfilter === $message)]; }
    public static function getFilteredText($text) { self::filterText($text); return $text; }
    public static function isAllowed($text): bool { $original = $text; self::filterText($text); return $original === $text; }
    public static function filterText(&$text, $charset = 'UTF-8'): string {
        if ($charset !== ($utf8 = 'UTF-8')) $text = iconv($charset, $utf8, $text);
        preg_match_all('/
\b\d*(
	\w*[' . self::$LT_P . '][' . self::$LT_I . self::$LT_E . '][' . self::$LT_Z . '][' . self::$LT_D . ']\w* # пизда
|
	(?:[^' . self::$LT_I . self::$LT_U . '\s]+|' . self::$LT_N . self::$LT_I . ')?(?<!стра)[' . self::$LT_H . '][' . self::$LT_U . '][' . self::$LT_YI . self::$LT_E . self::$LT_YA . self::$LT_YO . self::$LT_I . self::$LT_L . self::$LT_YU . '](?!иг)\w* # хуй; не пускает "подстрахуй", "хулиган"
|
	\w*[' . self::$LT_B . '][' . self::$LT_L . '](?:
		[' . self::$LT_YA . ']+[' . self::$LT_D . self::$LT_T . ']?
		|
		[' . self::$LT_I . ']+[' . self::$LT_D . self::$LT_T . ']+
		|
		[' . self::$LT_I . ']+[' . self::$LT_A . ']+
	)(?!х)\w* # бля, блядь; не пускает "бляха"
|
	(?:
		\w*[' . self::$LT_YI . self::$LT_U . self::$LT_E . self::$LT_A . self::$LT_O . self::$LT_HS . self::$LT_SS . self::$LT_Y . self::$LT_YA . '][' . self::$LT_E . self::$LT_YO . self::$LT_YA . self::$LT_I . '][' . self::$LT_B . self::$LT_P . '](?!ы\b|ол)\w* # не пускает "еёбы", "наиболее", "наибольшее"...
		|
		[' . self::$LT_E . self::$LT_YO . '][' . self::$LT_B . ']\w*
		|
		[' . self::$LT_I . '][' . /*self::$LT_P .*/ self::$LT_B . '][' . self::$LT_A . ']\w+
		|
		[' . self::$LT_YI . '][' . self::$LT_O . '][' . self::$LT_B . self::$LT_P . ']\w*
	) # ебать
|
	\w*[' . self::$LT_S . '][' . self::$LT_C . ']?[' . self::$LT_U . ']+(?:
		[' . self::$LT_CH . ']*[' . self::$LT_K . ']+
		|
		[' . self::$LT_CH . ']+[' . self::$LT_K . ']*
	)[' . self::$LT_A . self::$LT_O . ']\w* # сука
|
	\w*(?:
		[' . self::$LT_P . '][' . self::$LT_I . self::$LT_E . '][' . self::$LT_D . '][' . self::$LT_A . self::$LT_O . self::$LT_E/* . self::$LT_I*/ . ']?[' . self::$LT_R . '](?!о)\w* # не пускает "Педро"
		|
		[' . self::$LT_P . '][' . self::$LT_E . '][' . self::$LT_D . '][' . self::$LT_E . self::$LT_I . ']?[' . self::$LT_G . self::$LT_K . ']
	) # пидарас
|
	\w*[' . self::$LT_Z . '][' . self::$LT_A . self::$LT_O . '][' . self::$LT_L . '][' . self::$LT_U . '][' . self::$LT_P . ']\w* # залупа
|
	\w*[' . self::$LT_M . '][' . self::$LT_A . '][' . self::$LT_N . '][' . self::$LT_D . '][' . self::$LT_A . self::$LT_O . ']\w* # манда
)\b
/xu', $text, $m);

        $c = count($m[1]);
        /*
        $exclusion=array('хлеба','наиболее');
        $m[1]=array_diff($m[1],$exclusion);
        */
        if ($c > 0) {
            for ($i = 0; $i < $c; $i++) {
                $word = $m[1][$i];
                $word = mb_strtolower($word, $utf8);
                foreach (self::$exceptions as $x) { if (mb_strpos($word, $x) !== false) { if (is_array(self::$logEx)) { $t = &self::$logEx[$m[1][$i]]; ++$t; } $word = false; unset($m[1][$i]); break; } }
                if ($word) $m[1][$i] = str_replace(array(' ', ',', ';', '.', '!', '-', '?', "\t", "\n"), '', $m[1][$i]);
            }
            $m[1] = array_unique($m[1]);
            $asterisks = array();
            foreach ($m[1] as $word) { if (is_array(self::$log)) { $t = &self::$log[$word]; ++$t; } $asterisks[] = str_repeat('*', mb_strlen($word, $utf8)); }
            $text = str_replace($m[1], $asterisks, $text);
            if ($charset !== $utf8) $text = iconv($utf8, $charset, $text);
            return $text;
        } else {
            if ($charset !== $utf8) $text = iconv($utf8, $charset, $text);
            return $text;
        }
    }
}
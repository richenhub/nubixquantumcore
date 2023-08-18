<?php 

namespace Richen;

use pocketmine\utils\Config;

use pocketmine\utils\TextFormat as C;

define('SPC', ' ');

class NubixLang extends Engine\Manager {
    public static Config $config;
    const SYM = '[!]';
    const ERR = C::DARK_RED . self::SYM;
    const SUC = C::DARK_GREEN . self::SYM;
    const WRN = C::GOLD . self::SYM;
    const INF = C::DARK_AQUA . self::SYM;

    public function __construct(Config $config) { self::$config = $config; }
    public function conf(): Config { return self::$config; }

    public function formatText($text) {
        $formattedText = '';
        $useAngleBrackets = true;
        
        $rus = false;
        $eng = false;
        $int = false;
        $cmd = false;

        $iserr = is_string(mb_stristr($text, '§4[!]'));
        $iswrn = is_string(mb_stristr($text, '§6[!]'));
        $issuc = is_string(mb_stristr($text, '§2[!]'));

        if ($issuc) $scheme = ['§a', '§2', '§f', '§7'];
        elseif ($iswrn) $scheme = ['§e', '§6', '§f', '§7'];
        elseif ($iserr) $scheme = ['§c', '§4', '§f', '§7'];
        else $scheme = ['§f', '§b', '§6', '§e'];

        for ($i = 0; $i < mb_strlen($text); $i++) {
            $char = mb_substr($text, $i, 1);
            if ($char === '§') {
                $formattedText .= $char . mb_substr($text, ++$i, 1);
            } elseif ($char === '/' && !$cmd) {
                $cmd = true;
                $formattedText .= '§f' . $char;
            } elseif ($cmd && $char !== '-') {
                $formattedText .= $char;
            } elseif ($cmd && $char === '-') {
                $formattedText .= $scheme[3] . $char;
            } elseif (preg_match('/[а-яА-Я]/u', $char) && !$rus) {
                $formattedText .= $scheme[0] . $char;
                $rus = true; $eng = false; $int = false;
            } elseif (preg_match('/[a-zA-Z]/u', $char) && !$eng) {
                $formattedText .= $scheme[2] . $char;
                $eng = true; $rus = false; $int = false;
            } elseif (preg_match('/[0-9]/u', $char) && !$int) {
                $formattedText .= $scheme[3] . $char;
                $int = true; $rus = false; $eng = false;
            //} elseif (preg_match('/^[^\p{L}\p{N}]+$/u', $char)) {
                //$formattedText .= $scheme[3] . $char;
            } else {
                $formattedText .= $char;
            }
        }
        return $formattedText;
        
        for ($i = 0; $i < mb_strlen($text); $i++) {
            $char = mb_substr($text, $i, 1);
            $ord = ord($char);

            if ($char === '§') {
                $formattedText .= $char . mb_substr($text, $i+1, 1);
                $i++;
            } elseif ($useAngleBrackets && $char === '<') {
                $formattedText .= '§f' . $char;
                $useAngleBrackets = false;
                $y = 0;
                $found = false;
                $newformattedText = '§f';
                for ($x = $i; $x < mb_strlen($text); $x++) {
                    $ch = mb_substr($text, $i, 1);
                    $y++;
                    if ($ch === '>') { $found = true; break; }
                }
            } elseif (!$useAngleBrackets && $char === '>') {
                $formattedText .= '§f' . $char;
                $useAngleBrackets = true;
            } elseif (preg_match('/[а-яА-Я]/u', $char)) {
                $formattedText .= '§7' . $char;
            } elseif (preg_match('/[a-zA-Z]/u', $char)) {
                $formattedText .= '§6' . $char;
            } elseif (preg_match('/[0-9]/u', $char)) {
                $formattedText .= '§6' . $char;
            } else {
                $formattedText .= $char;
            }
        }
        
        return $formattedText;
    }

    public function err(string $label, array $args = []) { return $this->prepare($label, self::ERR, $args); }
    public function suc(string $label, array $args = []) { return $this->prepare($label, self::SUC, $args); }
    public function wrn(string $label, array $args = []) { return $this->prepare($label, self::WRN, $args); }
    public function inf(string $label, array $args = []) { return $this->prepare($label, self::INF, $args); }
    public function prepare(string $label, string $pref = '', array $args = []) { return $this->formatText(($pref === '' ? '' : $pref . SPC) . sprintf(($this->conf()->get(mb_strtolower($label)) ?? $label), ...$args)); }
}
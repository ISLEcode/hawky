<?php

namespace Hawky;

use \Hawky\Set as HawkySet;

trait LangTrait {

    // use \Hawky\Toolbox;

    // Load language settings from file
    public function loadlangs ($fileName) {
        $path = dirname($fileName);
        $regex = "/^".basename($fileName)."$/";
        foreach (fslist ($path, $regex, true, false) as $entry) {
            exec_trace (2, "HawkyLanguage::load file:$entry<br/>");
            $this->_nls_ts = max($this->_nls_ts, filemtime($entry));
            $fileData = fsread ($entry);
            $settings = $this->yamlget ($fileData, "language");
            foreach($settings as $language=>$block) {
                if (!isset($this->_nls[$language])) {
                    $this->_nls[$language] = $block;
                } else {
                    foreach ($block as $key=>$value) {
                        $this->_nls[$language][$key] = $value;
                    }
                }
            }
        }
    }

    // Set current language
    public function setlang ($language) {
        $this->_currentlang = $language;
    }

    // Set default language setting
    public function setnlsdefault ($key) {
        $this->_nlsdefaults[$key] = true;
    }

    // Set language setting
    public function setnlskey ($key, $value, $language) {
        if (!isset($this->_nls[$language])) $this->_nls[$language] = new HawkySet();
        $this->_nls[$language][$key] = $value;
    }

    // Return language setting
    public function getnlskey ($key, $lang = '', $escaped = false) {
        if (empty ($lang)) $lang = $this->_currentlang;
        $value = $this->nlstest ($key, $lang) ? $this->_nls[$lang][$key] : "[$key]";
        return $escaped ? htmlspecialchars($value) : $value;
    }

    // Return human readable date
    public function getnlsdate ($timestamp, $format, $language = "") {
        $dateMonths = preg_split("/\s*,\s*/", $this->getText("coreDateMonths", $language));
        $dateWeekdays = preg_split("/\s*,\s*/", $this->getText("coreDateWeekdays", $language));
        $month = $dateMonths[date("n", $timestamp) - 1];
        $weekday = $dateWeekdays[date("N", $timestamp) - 1];
        $timeZone = $this->rcget("site-timezone");
        $timeZoneHelper = new DateTime(null, new DateTimeZone($timeZone));
        $timeZoneOffset = $timeZoneHelper->getOffset();
        $timeZoneAbbreviation = "GMT".($timeZoneOffset<0 ? "-" : "+").abs(intval($timeZoneOffset/3600));
        $format = preg_replace("/(?<!\\\)F/", addcslashes($month, "A..Za..z"), $format);
        $format = preg_replace("/(?<!\\\)M/", addcslashes(mb_substr($month, 0, 3), "A..Za..z"), $format);
        $format = preg_replace("/(?<!\\\)D/", addcslashes(mb_substr($weekday, 0, 3), "A..Za..z"), $format);
        $format = preg_replace("/(?<!\\\)l/", addcslashes($weekday, "A..Za..z"), $format);
        $format = preg_replace("/(?<!\\\)T/", addcslashes($timeZoneAbbreviation, "A..Za..z"), $format);
        return date($format, $timestamp);
    }

    // Return human readable date, relative to today
    public function getnlsrdate ($timestamp, $format, $daysLimit, $language = "") {
        $timeDifference = time() - $timestamp;
        $days = abs(intval($timeDifference / 86400));
        $key = $timeDifference>=0 ? "coreDatePast" : "coreDateFuture";
        $tokens = preg_split("/\s*,\s*/", $this->getText($key, $language));
        if (count($tokens)>=8) {
            if ($days<=$daysLimit || $daysLimit==0) {
                if ($days==0) {
                    $output = $tokens[0];
                } elseif ($days==1) {
                    $output = $tokens[1];
                } elseif ($days>=2 && $days<=29) {
                    $output = preg_replace("/@x/i", $days, $tokens[2]);
                } elseif ($days>=30 && $days<=59) {
                    $output = $tokens[3];
                } elseif ($days>=60 && $days<=364) {
                    $output = preg_replace("/@x/i", intval($days/30), $tokens[4]);
                } elseif ($days>=365 && $days<=729) {
                    $output = $tokens[5];
                } else {
                    $output = preg_replace("/@x/i", intval($days/365.25), $tokens[6]);
                }
            } else {
                $output = preg_replace("/@x/i", $this->getDateFormatted($timestamp, $format, $language), $tokens[7]);
            }
        } else {
            $output = "[$key]";
        }
        return $output;
    }

    // Return language settings
    public function nlssettings ($filterStart = '', $filterEnd = '', $language = '') {
        $settings = array();
        if (empty($language)) $language = $this->_currentlang;
        if (isset($this->_nls[$language])) {
            if (empty($filterStart) && empty($filterEnd)) {
                $settings = $this->_nls[$language]->getArrayCopy();
            } else {
                foreach ($this->_nls[$language] as $key=>$value) {
                    if (!empty($filterStart) && mb_substr($key, 0, mb_strlen($filterStart))==$filterStart) $settings[$key] = $value;
                    if (!empty($filterEnd) && mb_substr($key, -mb_strlen($filterEnd))==$filterEnd) $settings[$key] = $value;
                }
            }
        }
        return $settings;
    }

    // Return language settings modification date, Unix time or HTTP format
    public function nlsrevision ($httpFormat = false) {
        return $httpFormat ? strw3time ($this->_nls_ts) : $this->_nls_ts;
    }

    // Normalise date into known format
    public function getnlscdate ($text, $language = "") {
        if (preg_match("/^\d+\-\d+$/", $text)) {
            $output = $this->getDateFormatted(strtotime($text), $this->getText("coreDateFormatShort", $language), $language);
        } elseif (preg_match("/^\d+\-\d+\-\d+$/", $text)) {
            $output = $this->getDateFormatted(strtotime($text), $this->getText("coreDateFormatMedium", $language), $language);
        } elseif (preg_match("/^\d+\-\d+\-\d+ \d+\:\d+$/", $text)) {
            $output = $this->getDateFormatted(strtotime($text), $this->getText("coreDateFormatLong", $language), $language);
        } else {
            $output = $text;
        }
        return $output;
    }

    // Check if language setting exists
    public function nlstest ($key, $language = "") {
        if (empty($language)) $language = $this->_currentlang;
        return isset($this->_nls[$language]) && isset($this->_nls[$language][$key]);
    }

    // Check if language exists
    public function islang ($language) {
        return isset($this->_nls[$language]);
    }
}


// vim: nospell

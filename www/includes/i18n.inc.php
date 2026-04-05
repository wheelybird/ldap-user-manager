<?php
// Initialize translations and load each language pack.
$TRANSLATIONS = array();

foreach (glob(__DIR__ . '/i18n/*.php') as $language) {
  require_once $language;
}

function current_ui_language($default = 'en') {
  global $LDAP;

  $sources = array(
    array(isset($_GET['lang']) ? array($_GET['lang']) : null, true),
    array(isset($_SESSION['ui_language']) ? array($_SESSION['ui_language']) : null, false),
    array(isset($LDAP['full_user_attributes']['preferredLanguage'][0]) ? array($LDAP['full_user_attributes']['preferredLanguage'][0]) : null, true),
    array(isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? accept_language_candidates($_SERVER['HTTP_ACCEPT_LANGUAGE']) : null, true),
  );
  foreach ($sources as $source) {
    if ($source[0] !== null && ($lang = pick_ui_language($source[0], $source[1])) !== null) return $lang;
  }

  return $default;
}

function normalize_ui_language($lang) {
  $lang = str_replace('_', '-', trim(preg_replace('/,.*$/', '', $lang)));
  if ($lang === '') return 'en';

  $parts = array_values(array_filter(array_map('trim', explode('-', $lang)), 'strlen'));
  if (empty($parts)) return 'en';

  $parts[0] = strtolower($parts[0]);
  if (isset($parts[1]) && strlen($parts[1]) === 2 && ctype_alpha($parts[1])) {
    $parts[1] = strtoupper($parts[1]);
  }

  for ($i = 2; $i < count($parts); $i++) $parts[$i] = strtolower($parts[$i]);

  return implode('-', $parts);
}

function is_language_available($lang) {
  global $TRANSLATIONS;
  return isset($TRANSLATIONS[$lang]);
}

function pick_ui_language($candidates, $remember = false) {
  foreach ($candidates as $candidate) {
    $lang = normalize_ui_language($candidate);
    if (is_language_available($lang)) {
      if ($remember) {
        $_SESSION['ui_language'] = $lang;
      }
      return $lang;
    }
  }

  return null;
}

function accept_language_candidates($accept_language) {
  $candidates = array();

  $parts = explode(',', $accept_language);
  foreach ($parts as $part) {
    $token = trim(preg_replace('/;.*/', '', $part));
    if ($token !== '') {
      $candidates[] = $token;
    }
  }

  return $candidates;
}

function t($key, $params = array()) {
  global $TRANSLATIONS;
  
  $lang = current_ui_language();

  if (isset($TRANSLATIONS[$lang][$key])) {
    $value = $TRANSLATIONS[$lang][$key];
  }
  elseif (isset($TRANSLATIONS['en'][$key])) {
    $value = $TRANSLATIONS['en'][$key];
  }
  else {
    return;
  }

  foreach ($params as $param => $val) {
    $value = str_replace('{' . $param . '}', $val, $value);
  }
  
  return $value;
}

function lang_url($path, $language = null) {
  if ($language === null) {
    $language = current_ui_language();
  }

  $language = normalize_ui_language($language);

  $fragment = '';
  $hash_pos = strpos($path, '#');
  if ($hash_pos !== false) {
    $fragment = substr($path, $hash_pos);
    $path = substr($path, 0, $hash_pos);
  }

  $query = array();
  $base_path = $path;
  $query_pos = strpos($path, '?');
  if ($query_pos !== false) {
    $base_path = substr($path, 0, $query_pos);
    parse_str(substr($path, $query_pos + 1), $query);
  }

  $query['lang'] = $language;
  $query_string = http_build_query($query);

  return $base_path . ($query_string !== '' ? '?' . $query_string : '') . $fragment;
}

function language_switch_url($language) {
  $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
  return lang_url($request_uri, $language);
}

function language_native_label($language) {
  global $TRANSLATIONS;

  $code = str_replace('_', '-', trim((string)$language));
  $normalized = normalize_ui_language($code);

  foreach (array($code, $normalized) as $candidate) {
    if (isset($TRANSLATIONS[$candidate]['meta.language_name'])) return $TRANSLATIONS[$candidate]['meta.language_name'];
    if ($candidate === $normalized) break;
  }

  return strtoupper($code);
}

function language_menu_options($active_language = null) {
  global $TRANSLATIONS;
  static $base_options = null;

  $active_language = normalize_ui_language($active_language !== null ? $active_language : current_ui_language());
  if ($base_options === null) {
    $supported_languages = (isset($TRANSLATIONS) && is_array($TRANSLATIONS) && !empty($TRANSLATIONS)) ? array_keys($TRANSLATIONS) : array('en');
    $base_options = array();
    foreach ($supported_languages as $supported_language) {
      $code = normalize_ui_language($supported_language);
      $label = language_native_label($code);

      if (!isset($base_options[$label])) {
        $base_options[$label] = array(
          'code' => $code,
          'label' => $label,
          'active' => false,
        );
      }
    }
  }

  $options = $base_options;
  $active_label = language_native_label($active_language);
  if (isset($options[$active_label])) $options[$active_label] = array('code' => $active_language, 'label' => $options[$active_label]['label'], 'active' => true);

  return $options;
}

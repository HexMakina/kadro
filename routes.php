<?php
return [
  // --- auth
  ['GET', 'checkin', 'ReceptionController::checkin', 'checkin'],
  ['GET', 'checkout', 'ReceptionController::checkout', 'checkout'],
  ['POST', 'identify', 'ReceptionController::identify', 'identify'],
  ['GET', 'operator/[*:username]/toggle/active', 'OperatorController::change_active', 'operator_change_active'],
  ['GET', 'operator/[*:username]/change-acl/[i:permission_id]', 'OperatorController::change_acl', 'acl_toggle'],

  // --- SEARCH
  ['POST|GET', 'search', 'SearchController::results', 'search'],

  // --- TRADUKO
  ['POST', 'traduko/update_file', 'TradukoController::update_file', 'traduko_update_file'],

  // --- LOCALE JSON
  ['GET', 'locale/language_codes.[a:format]', 'ExportController::otto_languages', 'otto_languages'],

  // --- EXPORT
  ['GET', 'export', 'ExportController::dashboard', 'export'], // default ExportController is dashboard
  ['GET', 'otto/language_codes.[a:format]/term/[a:search]?', 'ExportController::otto_languages', 'otto_languages_search'],
  ['GET', 'otto/[a:model]/distinct/[*:field].[a:format]', 'ExportController::otto_distinct_field', 'otto_distinct_field'],
  ['GET', 'otto/[a:model]/distinct/[*:field].[a:format]/term/[*:search]?', 'ExportController::otto_distinct_field', 'otto_distinct_field_where'],
  ['GET', 'export/[*:action].[a:format]', 'ExportController::dynamic_action_call', 'export_action_call'],
];

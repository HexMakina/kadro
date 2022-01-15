<?php

return [
  // --- auth
  ['GET', 'checkin', 'Reception::checkin', 'checkin'],
  ['GET', 'checkout', 'Reception::checkout', 'checkout'],
  ['POST', 'identify', 'Reception::identify', 'identify'],
  ['GET', 'operator/[*:username]/toggle/active', 'Operator::change_active', 'operator_change_active'],
  ['GET', 'operator/[*:username]/change-acl/[i:permission_id]', 'Operator::change_acl', 'acl_toggle'],

  // --- SEARCH
  ['POST|GET', 'search', 'Search::results', 'search'],

  // --- TRADUKO
  ['POST', 'traduko/update_file', 'Traduko::update_file', 'traduko_update_file'],

  // --- LOCALE JSON
  ['GET', 'locale/language_codes.[a:format]', 'Export::otto_languages', 'otto_languages'],

  // --- EXPORT
  ['GET', 'export', 'Export::dashboard', 'export'], // default ExportController is dashboard
  ['GET', 'otto/language_codes.[a:format]/term/[a:search]?', 'Export::otto_languages', 'otto_languages_search'],
  ['GET', 'otto/[a:model]/distinct/[*:field].[a:format]', 'Export::otto_distinct_field', 'otto_distinct_field'],
  ['GET', 'otto/[a:model]/distinct/[*:field].[a:format]/term/[*:search]?', 'Export::otto_distinct_field', 'otto_distinct_field_where'],
  ['GET', 'export/[*:action].[a:format]', 'Export::dynamic_action_call', 'export_action_call'],
];

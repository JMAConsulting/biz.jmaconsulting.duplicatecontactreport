<?php
// This file declares a managed database record of type "ReportTemplate".
// The record will be automatically inserted, updated, or deleted from the
// database as appropriate. For more details, see "hook_civicrm_managed" at:
// http://wiki.civicrm.org/confluence/display/CRMDOC42/Hook+Reference
return array (
  0 =>
  array (
    'name' => 'CRM_Report_Form_Contact_DuplicateContact',
    'entity' => 'ReportTemplate',
    'params' =>
    array (
      'version' => 3,
      'label' => ts('Duplicate Contact'),
      'description' => ts('Duplicate Contact (biz.jmaconsulting.duplicatecontactreport)'),
      'class_name' => 'CRM_Report_Form_Contact_DuplicateContact',
      'report_url' => 'contact/duplicatecontact',
      'component' => '',
    ),
  ),
);

<?php
use CRM_Duplicatecontactreport_ExtensionUtil AS E;

class CRM_Report_Form_Contact_DuplicateContact extends CRM_Report_Form {

  /**
   * Class constructor.
   */
  function __construct() {
    $this->_columns = [
      'civicrm_contact' => [
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => [
          'dst_contact_id' => [
            'no_display' => TRUE,
            'required' => TRUE,
            'dbAlias' => 'dst_contact_id',
          ],
          'dst_contact_id' => [
            'no_display' => TRUE,
            'required' => TRUE,
          ],
          'src_contact_id' => [
            'no_display' => TRUE,
            'required' => TRUE,
          ],
          'dst_contact_type' => [
            'title' => E::ts('Contact Type'),
            'no_repeat' => TRUE,
            'required' => TRUE,
          ],
          'dst_display_name' => [
            'title' => E::ts('Contact 1'),
            'no_repeat' => TRUE,
            'required' => TRUE,
          ],
          'src_display_name' => [
            'title' => E::ts('Contact 2(Duplicate)'),
            'no_repeat' => TRUE,
            'required' => TRUE,
          ],
          'dst_email' => [
            'title' => E::ts('Email 1'),
            'no_repeat' => TRUE,
            'required' => TRUE,
          ],
          'src_email' => [
            'title' => E::ts('Email 2(Duplicate)'),
            'no_repeat' => TRUE,
            'required' => TRUE,
          ],
        ],
      ],
    ];
    parent::__construct();
  }

  /**
   * Build the from clause.
   *
   *
   */
  function from() {
    $sqlQueries = [];
    $contactTypes = [
      'Individual' => 'display_name',
      'Organization' => 'organization_name',
      'Household' => 'household_name',
    ];
    foreach ($contactTypes AS $contactType => $colName) {
      $rgBao = new CRM_Dedupe_BAO_RuleGroup();
      $rgBao->contact_type = $contactType;
      $rgBao->used = 'Unsupervised';
      if (!$rgBao->find(TRUE)) {
        CRM_Core_Error::fatal("Unsupervised rule for $contactType does not exist");
      }
      $ruleGroupId = $rgBao->id;
      $cacheKeyString = "duplicateReport_{$contactType}_$ruleGroupId";
      CRM_Core_BAO_PrevNextCache::refillCache($ruleGroupId, NULL, $cacheKeyString, [], TRUE);
      $sqlQueries[$contactType] = " SELECT
          cc1.id AS dst_contact_id,
          cc2.id AS src_contact_id,
          cc1.contact_type AS dst_contact_type,
          cc1.{$colName} AS dst_display_name,
          cc2.contact_type AS src_contact_type,
          cc2.{$colName} AS src_display_name,
          ce1.email AS dst_email,
          ce2.email AS src_email
        FROM civicrm_prevnext_cache pn
          LEFT JOIN civicrm_dedupe_exception de
            ON (
              pn.entity_id1 = de.contact_id1
              AND pn.entity_id2 = de.contact_id2
            )
          INNER JOIN civicrm_contact cc1 ON cc1.id = pn.entity_id1
          INNER JOIN civicrm_contact cc2 ON cc2.id = pn.entity_id2
          LEFT JOIN civicrm_email ce1 ON (ce1.contact_id = pn.entity_id1 AND ce1.is_primary = 1)
          LEFT JOIN civicrm_email ce2 ON (ce2.contact_id = pn.entity_id2 AND ce2.is_primary = 1)
        WHERE pn.cacheKey = '{$cacheKeyString}' AND de.id IS NULL
      ";
    }
    $this->_from = "
      FROM (
        " . implode(' UNION ', $sqlQueries) . "
        ) AS {$this->_aliases['civicrm_contact']}
    ";
  }

  /**
   * Build order by clause.
   */
  function orderBy() {
    $this->_orderBy = " ORDER BY
      {$this->_aliases['civicrm_contact']}.dst_display_name,
      {$this->_aliases['civicrm_contact']}.dst_contact_id
    ";
  }

  /**
   * Do AlterDisplay processing on Contact Fields.
   *
   * @param array $rows
   *
   */
  function alterDisplay(&$rows) {
    $title = E::ts("View Contact Summary for this Contact.");
    $entryFound = FALSE;
    foreach ($rows as $rowNum => $row) {
      if (CRM_Utils_Array::value('civicrm_contact_dst_contact_id', $row)
        && CRM_Utils_Array::value('civicrm_contact_src_contact_id', $row)
      ) {
        $contactUrl1 = CRM_Utils_System::url("civicrm/contact/view",
          'reset=1&cid=' . $row['civicrm_contact_dst_contact_id'],
          $this->_absoluteUrl
        );
        $contactUrl2 = CRM_Utils_System::url("civicrm/contact/view",
          'reset=1&cid=' . $row['civicrm_contact_src_contact_id'],
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_contact_dst_display_name'] = sprintf('<a href="%s" title="%s">%s</a>',
          $contactUrl1,
          $title,
          $rows[$rowNum]['civicrm_contact_dst_display_name']
        );
        $rows[$rowNum]['civicrm_contact_src_display_name'] = sprintf('<a href="%s" title="%s">%s</a>',
          $contactUrl2,
          $title,
          $rows[$rowNum]['civicrm_contact_src_display_name']
        );
        $entryFound = TRUE;
      }

      if (!$entryFound) {
        break;
      }
    }
  }

}

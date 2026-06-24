<?php declare(strict_types=1);

namespace PayCal\Domain;

?>
  <details class="panel businesses_governance_panel businesses_definitions_panel" aria-labelledby="businesses_hierarchy_guide_title">
    <summary class="businesses_governance_summary">
      <h2 id="businesses_hierarchy_guide_title"><?php echo businesses_index_i18n_html('BUSINESSES_HIERARCHY_TITLE'); ?></h2>
    </summary>
    <p class="help_text businesses_hierarchy_intro"><?php echo businesses_index_i18n_html('BUSINESSES_HIERARCHY_INTRO'); ?></p>
    <p class="help_text businesses_hierarchy_ownership_note" role="note"><?php echo businesses_index_i18n_html('BUSINESSES_HIERARCHY_OWNERSHIP_NOTE'); ?></p>
    <div class="businesses_permission_matrix">
      <div class="businesses_hierarchy_table_wrap businesses_permission_table_desktop">
        <table class="businesses_hierarchy_table">
          <thead>
            <tr>
              <th scope="col">Permission / Feature</th>
              <?php foreach ($businessPermissionRoles as $roleColumn) { ?>
                <th scope="col"><?php echo htmlspecialchars((string) $roleColumn['label'], ENT_QUOTES, 'UTF-8'); ?></th>
              <?php } ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($businessPermissionMatrix as $permissionRow) { ?>
              <tr>
                <th scope="row"><?php echo htmlspecialchars((string) $permissionRow['feature'], ENT_QUOTES, 'UTF-8'); ?></th>
                <?php foreach ($businessPermissionRoles as $roleColumn) { ?>
                  <td><?php echo htmlspecialchars((string) ($permissionRow[$roleColumn['key']] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                <?php } ?>
              </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
      <div class="businesses_permission_cards businesses_permission_table_mobile" aria-label="<?php echo businesses_index_i18n_html('BUSINESSES_HIERARCHY_TITLE'); ?>">
        <?php foreach ($businessPermissionMatrix as $permissionRow) { ?>
          <article class="businesses_permission_card">
            <h3 class="businesses_permission_card_title"><?php echo htmlspecialchars((string) $permissionRow['feature'], ENT_QUOTES, 'UTF-8'); ?></h3>
            <ul class="businesses_permission_role_chips">
              <?php foreach ($businessPermissionRoles as $roleColumn) {
                $value = (string) ($permissionRow[$roleColumn['key']] ?? '-');
                $chipClass = $value === '✓' ? 'is-granted' : ($value === '-' ? 'is-denied' : 'is-scope');
                ?>
                <li class="businesses_permission_role_chip <?php echo $chipClass; ?>">
                  <span class="businesses_permission_role_name"><?php echo htmlspecialchars((string) $roleColumn['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                  <span class="businesses_permission_role_value"><?php echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?></span>
                </li>
              <?php } ?>
            </ul>
          </article>
        <?php } ?>
      </div>
    </div>
  </details>

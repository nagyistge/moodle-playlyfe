<?php

function xmldb_local_playlyfe_upgrade($oldversion) {
   global $CFG, $DB, $OUTPUT;
    $dbman = $DB->get_manager();

    $result = TRUE;

    if ($oldversion < 2014112718) {

        // Define table local_playlyfe to be created.
        $table = new xmldb_table('local_playlyfe');

        // Adding fields to table local_playlyfe.
        $table->add_field('id', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
        $table->add_field('event', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table local_playlyfe.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for local_playlyfe.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Playlyfe savepoint reached.
        upgrade_block_savepoint(true,  2014112718, 'playlyfe');
    }


    return $result;
}

?>

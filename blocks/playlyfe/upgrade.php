<?php

function xmldb_mymodule_upgrade($oldversion) {
    global $CFG;

    $result = TRUE;

    if ($oldversion < 2014111015) {

        // Define table block_playlyfe to be created.
        $table = new xmldb_table('block_playlyfe');

        // Adding fields to table block_playlyfe.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('access_token', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
        $table->add_field('refresh_token', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
        $table->add_field('expires_at', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table block_playlyfe.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for block_playlyfe.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Playlyfe savepoint reached.
        upgrade_block_savepoint(true, 2014111015, 'playlyfe');
    }

    return $result;
}

?>

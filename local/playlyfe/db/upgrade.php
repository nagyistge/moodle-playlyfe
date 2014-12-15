<?php

function xmldb_local_playlyfe_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    $result = TRUE;
    $current_version =2014112725;

    // if ($oldversion < $current_version) {

    //     // Define table local_playlyfe to be created.
    //     $table = new xmldb_table('local_playlyfe');

    //     // Adding fields to table local_playlyfe.
    //     $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    //     $table->add_field('course', XMLDB_TYPE_INTEGER, '15', null, XMLDB_NOTNULL, null, null);
    //     $table->add_field('metric', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
    //     $table->add_field('verb', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, null);
    //     $table->add_field('value', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
    //     $table->add_field('value2', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);

    //     // Adding keys to table local_playlyfe.
    //     $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

    //     // Conditionally launch create table for local_playlyfe.
    //     if (!$dbman->table_exists($table)) {
    //         $dbman->create_table($table);
    //     }

    //     // Playlyfe savepoint reached.
    //     upgrade_plugin_savepoint(true, $current_version, 'local', 'playlyfe');
    // }


    return $result;
}

?>

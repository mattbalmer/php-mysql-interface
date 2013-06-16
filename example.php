<?
// ===========================================================
// ===== Include the DB Interface file (The boring part) =====
// ===========================================================

// Include the DB Handler file, and set your config variables here
include_once("/path/to/db.php");

// Set connection variables - 3 methods

    // Method 1 : Set static variables
    DB::$host = 'db_hostname(localhost / url.to.database.com';
    DB::$user = 'db_user_name';
    DB::$password = 'db_user_password';
    DB::$database = 'target_db_name';
    DB::$prefix = 'mPre_';  // Prefix optional - will apply to all table calls

    // Method 2 : Pass Variables
    DB::connect( $host, $database, $user, $password, $prefix ); // Prefix optional

    // Method 3 : Pass array
    $connectionInfo = array(
        'host' => $host,
        'database' => $database,
        'user' => $user,
        'password' => $password,
        'prefix' => $prefix // Prefix optional
    );
    DB::connect( $connectionInfo );

// ===================================================
// ===== Using the DB Interface ( The fun part ) =====
// ===================================================

// Connect to the Database
DB::connect();

// Define our test array
$myArray = array();

// Use a GET action on the database :: get( Table Name, Columns, Where, Order )
$dbArray = DB::get("mytable", "*", NULL, "id");

foreach($dbObject as $dbArray) {
    $myArray[] = array(
        'id' => $dbObject['id'],
        'name' => $dbObject['name'],
        'field' => $dbObject['field']
    );
}

// Enable error logging - is false by default
DB::setOption('log', true);

// Use INSERT action :: insert( Table Name, array(key, value) )
DB::insert('mytable', array(
    'id' => $my_id,
    'name' => $my_name,
    'field' => $my_field
));

// Use UPDATE action :: update( Table Name, array(key, value), Where )
DB::update('mytable', array(
    'name' => $new_name,
    'field' => $new_field
), "id=".$id);

// Use DELETE action :: delete( Table Name, array(key, value), Where )
DB::delete('mytable', "name=".$old_name);

// Close the DB Connection
DB::close();

?>
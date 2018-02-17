<?php
include('simplehtmldom_1_5/simple_html_dom.php');
// credentials for MySQL
$servername = "localhost:3306";
$username = "root";
$password = "asdfjkl;";
$dbname = "BiasSorter";

$inputGroups = $_POST["groupList"];
$groupList = array_map('trim', explode(",", $inputGroups)); //array of the groupnames
$numGroups = sizeof($groupList);
$past = 0;

//CREATE CONNECTION
$conn = mysqli_connect($servername, $username, $password, $dbname);
// CHECK CONNECTION
if (!$conn) {
  die("Connection failed: " . mysqli_connect_error());
}

function add_data($group, $member, $imgurl, $pastMem) {
  global $conn;
  $addtoTable = "INSERT INTO {$group} (NAME, IMGURL, PAST)
  VALUES ('{$member}', '{$imgurl}', '{$pastMem}')";
  if ($conn->query($addtoTable) === TRUE) {
    echo "Member {$member} added successfully";
  } else {
    echo "Error adding {$member}: " . $conn->error;
  }
}

// fetches image from pinterest and returns the url
function fetch_image($group, $member) {
  $search = "{$group} {$member}";
  // echo $search . "\n";
  $search_query = urlencode($search);
  $html_img = file_get_contents("https://www.pinterest.ca/search/pins/?q={$search_query}");
  $dom_img = new DOMDocument();
  @$dom_img->loadHTML($html_img);
  $images = $dom_img->getElementsByTagName("img");
  $img_num = rand(0, $images->length - 1);
  $src = $images[$img_num]->getAttribute('src');
  return $src;
}

// iterate through list of groups entered and either pull data
// from database or add to database if doesn't exist
foreach ($groupList as &$name) {
  $name = strtolower(str_replace(' ', '_', $name));

  // sql to create table in database
  $createTable = "CREATE TABLE {$name}
  (NAME varchar(255),
  IMGURL varchar(255),
  PAST int);";

  if ($conn->query($createTable) === TRUE) {
    echo "Table {$name} created successfully";
  } else {
    echo "Error creating table: " . $conn->error;
  }

  $search_query = urlencode($name);
  $html_wiki = file_get_html("http://kpop.wikia.com/wiki/apink");
  $dom_wiki = new DOMDocument;
  @$dom_wiki->loadHTML($html_wiki);

  // echo $dom_wiki->getAttribute('title');
  $asides = $dom_wiki->getElementsByTagName("aside");

  foreach ($asides as $aside) {
    $types = $aside->getElementsByTagName("ul"); // gets current / past members
    foreach ($types as $type) {
      $members = $type->getElementsByTagName("li");

      foreach ($members as $member) {
        $mem_name = trim($member->nodeValue);
        // echo $mem_name . "\n";
        $img_url = fetch_image($name, $mem_name);
        // echo $img_url . "\n";

        add_data($name, $mem_name, $img_url, $past);
      }
      $past = 1;
    }
  }
}
$conn->close();
?>

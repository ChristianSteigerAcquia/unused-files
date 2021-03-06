<?php
echo "Searching for unused files." . "\n";

$drupal_path = $argv[1];
$files_path = $drupal_path . $argv[2];
$uri = $argv[3];

$result = "orphan_file_results.txt";

$command = 'cd ' . $drupal_path . ' && drush sqlq "SELECT uri FROM file_managed" @uri';
$command = strtr($command, [
  '@uri' => '--uri=' . $uri,
]);

$filesManaged = explode("\n", shell_exec($command));

echo "Managed files collected." . "\n";

$command = "find @location -type f -follow -print | grep -v -E '@excludes' | sort -nr > filesONdisk.txt";
//$command = "ls -1 @location | grep -v -E '@excludes' > filesONdisk.txt";

$command = strtr($command, [
  '@location' => $files_path,
  '@excludes' => '/js/js_|/css/css_|/php/twig/|/styles/',
]);

explode("\n", shell_exec($command));
echo "Files on Disk collected." . "\n";

$filesDisk = file('filesONdisk.txt');

echo "Compare files...";

$comp = $comp1 = array();
foreach ($filesDisk as $file) {
  $comp[] = str_replace("\n", "", str_replace($files_path . "/", "", $file));

  echo ".";
}

foreach ($filesManaged as $value) {
  if (strpos($value, 'public://') === 0 || strpos($value, 'private://') === 0) {
    $value = str_replace('private://', "", $value);
    $value = str_replace('public://', "", $value);
    $comp1[] = $value;
  }
}

$orphan = array_filter(array_diff($comp, $comp1));

echo "\n" . "Start writing results file" . "\n";
$fp = fopen($result, 'w');

foreach ($orphan as $key => $value) {
  fwrite($fp, $value . "\n");
}
fclose($fp);

$message = "We did found @count orphan file(s), check out the details from @results " . "\n";
$message = strtr($message, [
  '@count'   => sizeof($orphan),
  '@results' => dirname(__FILE__) . "/" . $result,
]);

echo $message;

return;
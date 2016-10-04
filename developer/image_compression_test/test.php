<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 4/10/2016
 * Time: 11:31 AM
 */
set_time_limit(300);
$source_image = imagecreatefrompng('icon_social_media.png');

//$target_image = imagecreatetruecolor(1200,900);
//imagecopyresampled($target_image, $source_image, 0, 0, 0, 0, 1200, 900, imagesx($source_image),imagesy($source_image));
$target_image = $source_image;

$time = [];
$file_size = [];

for ($i=0;$i<10;$i++)
{
    $target_image_path = 'jpeg_'.($i+1)*10 . '.jpg';
    $start_time = microtime(true);
    imagejpeg($target_image, $target_image_path, ($i+1)*10);
    $time[$target_image_path] = microtime(true) - $start_time;
    $file_size[$target_image_path] = filesize($target_image_path);
}

echo '<h1>JPEG</h1><br>';
echo '<table>';
foreach($time as $file=>$file_time)
{
    echo '<tr><td>'.$file.'</td><td>'.$file_time.'</td><td>'.round($file_size[$file]/1024,2) . ' KB</td></tr>';
}
echo '</table>';


$time = [];
$file_size = [];
$png_filter = ['PNG_NO_FILTER', 'PNG_FILTER_NONE', 'PNG_FILTER_SUB', 'PNG_FILTER_UP', 'PNG_FILTER_AVG', 'PNG_FILTER_PAETH', 'PNG_ALL_FILTERS'];

for ($j=0;$j<7;$j++)
{
    $time[$png_filter[$j]] = [];
    $file_size[$png_filter[$j]] = [];
    for ($i=0;$i<10;$i++)
    {
        $target_image_path = 'png_' . $png_filter[$j] . '_' . $i . '.png';
        $start_time = microtime(true);

        imagesavealpha($target_image, true);
        imagepng($target_image,$target_image_path,$i,constant($png_filter[$j]));
        $time[$png_filter[$j]][$i] = microtime(true) - $start_time;
        $file_size[$png_filter[$j]][$i] = filesize($target_image_path);
    }
}
echo '<h1>PNG</h1><br>';
echo '<table>';
for ($j=0;$j<7;$j++)
{
    echo '<tr><td>'.$png_filter[$j].'</td>';
    for ($i=0;$i<10;$i++)
    {
        echo '<td>'.$time[$png_filter[$j]][$i].'</td>';
    }
    echo '</tr>';
}
echo '</table>';
echo '<table>';
for ($j=0;$j<7;$j++)
{
    echo '<tr><td>'.$png_filter[$j].'</td>';
    for ($i=0;$i<10;$i++)
    {
        echo '<td>'.round($file_size[$png_filter[$j]][$i]/1024,2) . ' KB</td>';
    }
    echo '</tr>';
}
echo '</table>';


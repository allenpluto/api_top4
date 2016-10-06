<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 4/10/2016
 * Time: 11:31 AM
 */
set_time_limit(300);

$time = [];
$file_size = [];

$target_image = imagecreatetruecolor(400,300);

for ($i=0;$i<10;$i++)
{
    $source_image_path = 'jpeg_'.($i+1)*10 . '.jpg';
    $target_image_path = 'jpeg_'.($i+1)*10 . '_resize.jpg';
    $time[$source_image_path] = array();

    $start_time = microtime(true);
    $source_image = imagecreatefromjpeg($source_image_path);
    $time[$source_image_path]['create'] = microtime(true) - $start_time;

    $start_time = microtime(true);
    imagecopyresized($target_image, $source_image, 0, 0, 0, 0, 400, 300, 1200,900);
    $time[$source_image_path]['resize'] = microtime(true) - $start_time;

    $start_time = microtime(true);
    imagejpeg($target_image, $target_image_path, 80);
    $time[$source_image_path]['resize_write'] = microtime(true) - $start_time;

    $target_image_path = 'jpeg_'.($i+1)*10 . '_resample.jpg';

    $start_time = microtime(true);
    imagecopyresampled($target_image, $source_image, 0, 0, 0, 0, 400, 300, 1200,900);
    $time[$source_image_path]['resample'] = microtime(true) - $start_time;

    $start_time = microtime(true);
    imagejpeg($target_image, $target_image_path, 80);
    $time[$source_image_path]['resample_write'] = microtime(true) - $start_time;
}

echo '<h1>JPEG</h1><br>';
echo '<table>';
echo '<tr><th>File</th><th>create</th><th>resize</th><th>resize_write</th><th>resize_file_size</th><th>resample</th><th>resample_write</th><th>resample_file_size</th></tr>';
foreach($time as $file=>$file_time)
{
    echo '<tr><td>'.$file.'</td><td>'.$file_time['create'].'</td><td>'.$file_time['resize'].'</td><td>'.$file_time['resize_write'].'</td><td>'.round(filesize(str_replace('.jpg','_resize.jpg',$file))/1024,2).'</td><td>'.$file_time['resample'].'</td><td>'.$file_time['resample_write'].'</td><td>'.round(filesize(str_replace('.jpg','_resample.jpg',$file))/1024,2).'</td></tr>';
}
echo '</table>';


$time = [];
$png_filter = ['PNG_NO_FILTER', 'PNG_FILTER_NONE', 'PNG_FILTER_SUB', 'PNG_FILTER_UP', 'PNG_FILTER_AVG', 'PNG_FILTER_PAETH', 'PNG_ALL_FILTERS'];

for ($j=0;$j<7;$j++)
{
    for ($i=0;$i<10;$i++)
    {
        $source_image_path = 'png_' . $png_filter[$j] . '_' . $i . '.png';
        $target_image_path = 'png_' . $png_filter[$j] . '_' . $i . '_resize.png';
        $time[$source_image_path] = array();

        $start_time = microtime(true);
        $source_image = imagecreatefrompng($source_image_path);
        $time[$source_image_path]['create'] = microtime(true) - $start_time;

        $start_time = microtime(true);
        imagecopyresized($target_image, $source_image, 0, 0, 0, 0, 400, 300, 1200,900);
        $time[$source_image_path]['resize'] = microtime(true) - $start_time;

        $start_time = microtime(true);
        imagepng($target_image, $target_image_path, 1, PNG_NO_FILTER);
        $time[$source_image_path]['resize_write'] = microtime(true) - $start_time;

        $target_image_path = 'png_' . $png_filter[$j] . '_' . $i . '_resample.png';

        $start_time = microtime(true);
        imagecopyresampled($target_image, $source_image, 0, 0, 0, 0, 400, 300, 1200,900);
        $time[$source_image_path]['resample'] = microtime(true) - $start_time;

        $start_time = microtime(true);
        imagepng($target_image, $target_image_path, 1, PNG_NO_FILTER);
        $time[$source_image_path]['resample_write'] = microtime(true) - $start_time;
    }
}
echo '<h1>PNG</h1><br>';
echo '<table>';
echo '<tr><th>File</th><th>create</th><th>resize</th><th>resize_write</th><th>resize_file_size</th><th>resample</th><th>resample_write</th><th>resample_file_size</th></tr>';
foreach($time as $file=>$file_time)
{
    echo '<tr><td>'.$file.'</td><td>'.$file_time['create'].'</td><td>'.$file_time['resize'].'</td><td>'.$file_time['resize_write'].'</td><td>'.round(filesize(str_replace('.png','_resize.png',$file))/1024,2).'</td><td>'.$file_time['resample'].'</td><td>'.$file_time['resample_write'].'</td><td>'.round(filesize(str_replace('.png','_resample.png',$file))/1024,2).'</td></tr>';
}
echo '</table>';

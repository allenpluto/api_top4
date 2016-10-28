<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 25/10/2016
 * Time: 3:00 PM
 */
if (!isset($global_preference)) $global_preference = preference::get_instance();

// Image Size (width grid)
$global_preference->image = array(
    'size'=>array(
        'xxs'=>45,
        'xs'=>90,
        's'=>180,
        'm'=>300,
        'l'=>480,
        'xl'=>800,
        'xxl'=>1200,
        ''=>1600
    ),
    'quality'=>array(
        // Minimize file size, for pre generated thumbnail
        'min'=>array(
            'image/jpeg'=>40,
            'image/png'=>array(9,PNG_ALL_FILTERS)
        ),
        // Small file size, with relatively high generate speed and good quality, default option for pre generated images
        'opt'=>array(
            'image/jpeg'=>80,
            'image/png'=>array(7,PNG_NO_FILTER)
        ),
        // Best Quality, default option for source images
        'max'=>array(
            'image/jpeg'=>95,
            'image/png'=>array(1,PNG_NO_FILTER)
        ),
        // Fast generate speed, with relatively small file size and good quality, default option for real time rendering images
        'spd'=>array(
            'image/jpeg'=>80,
            'image/png'=>array(1,PNG_FILTER_UP)
        )
    )
);

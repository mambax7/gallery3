<?php defined('SYSPATH') || die('No direct script access.');
//================================================================================================
//================================================================================================
/*
    Exifer
    Extracts EXIF information from digital photos.

    Copyright � 2003 Jake Olefsky
    http://www.offsky.com/software/exif/index.php
    jake@olefsky.com

    Please see exif.php for the complete information about this software.

    ------------

    This program is free software; you can redistribute it and/or modify it under the terms of
    the GNU General Public License as published by the Free Software Foundation; either version 2
    of the License, or (at your option) any later version.

    This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
    without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
    See the GNU General Public License for more details. http://www.gnu.org/copyleft/gpl.html
*/
//================================================================================================
//================================================================================================
//================================================================================================

//=================
// Looks up the name of the tag for the MakerNote (Depends on Manufacturer)
//====================================================================
function lookup_Fujifilm_tag($tag)
{
    switch ($tag) {
        case '0000':
            $tag = 'Version';
            break;
        case '1000':
            $tag = 'Quality';
            break;
        case '1001':
            $tag = 'Sharpness';
            break;
        case '1002':
            $tag = 'WhiteBalance';
            break;
        case '1003':
            $tag = 'Color';
            break;
        case '1004':
            $tag = 'Tone';
            break;
        case '1010':
            $tag = 'FlashMode';
            break;
        case '1011':
            $tag = 'FlashStrength';
            break;
        case '1020':
            $tag = 'Macro';
            break;
        case '1021':
            $tag = 'FocusMode';
            break;
        case '1030':
            $tag = 'SlowSync';
            break;
        case '1031':
            $tag = 'PictureMode';
            break;
        case '1100':
            $tag = 'ContinuousTakingBracket';
            break;
        case '1200':
            $tag = 'Unknown';
            break;
        case '1300':
            $tag = 'BlurWarning';
            break;
        case '1301':
            $tag = 'FocusWarning';
            break;
        case '1302':
            $tag = 'AEWarning';
            break;

        default:
            $tag = 'unknown:' . $tag;
            break;
    }

    return $tag;
}

//=================
// Formats Data for the data type
//====================================================================
function formatFujifilmData($type, $tag, $intel, $data)
{
    if ('ASCII' == $type) {
    } elseif ('URATIONAL' == $type || 'SRATIONAL' == $type) {
        $data = unRational($data, $type, $intel);

        if ('1011' == $tag) { //FlashStrength
            $data = $data . ' EV';
        }
    } elseif ('USHORT' == $type || 'SSHORT' == $type || 'ULONG' == $type || 'SLONG' == $type || 'FLOAT' == $type || 'DOUBLE' == $type) {
        $data = rational($data, $type, $intel);

        if ('1001' == $tag) { //Sharpness
            if (1 == $data) {
                $data = (string)t('Soft');
            } elseif (2 == $data) {
                $data = (string)t('Soft');
            } elseif (3 == $data) {
                $data = (string)t('Normal');
            } elseif (4 == $data) {
                $data = (string)t('Hard');
            } elseif (5 == $data) {
                $data = (string)t('Hard');
            } else {
                $data = (string)t('Unknown') . ': ' . $data;
            }
        }
        if ('1002' == $tag) { //WhiteBalance
            if (0 == $data) {
                $data = (string)t('Auto');
            } elseif (256 == $data) {
                $data = (string)t('Daylight');
            } elseif (512 == $data) {
                $data = (string)t('Cloudy');
            } elseif (768 == $data) {
                $data = (string)t('DaylightColor-fluorescence');
            } elseif (769 == $data) {
                $data = (string)t('DaywhiteColor-fluorescence');
            } elseif (770 == $data) {
                $data = (string)t('White-fluorescence');
            } elseif (1024 == $data) {
                $data = (string)t('Incandescence');
            } elseif (3840 == $data) {
                $data = (string)t('Custom');
            } else {
                $data = (string)t('Unknown') . ': ' . $data;
            }
        }
        if ('1003' == $tag) { //Color
            if (0 == $data) {
                $data = (string)t('Chroma Saturation Normal(STD)');
            } elseif (256 == $data) {
                $data = (string)t('Chroma Saturation High');
            } elseif (512 == $data) {
                $data = (string)t('Chroma Saturation Low(ORG)');
            } else {
                $data = (string)t('Unknown: ') . $data;
            }
        }
        if ('1004' == $tag) { //Tone
            if (0 == $data) {
                $data = (string)t('Contrast Normal(STD)');
            } elseif (256 == $data) {
                $data = (string)t('Contrast High(HARD)');
            } elseif (512 == $data) {
                $data = (string)t('Contrast Low(ORG)');
            } else {
                $data = (string)t('Unknown: ') . $data;
            }
        }
        if ('1010' == $tag) { //FlashMode
            if (0 == $data) {
                $data = (string)t('Auto');
            } elseif (1 == $data) {
                $data = (string)t('On');
            } elseif (2 == $data) {
                $data = (string)t('Off');
            } elseif (3 == $data) {
                $data = (string)t('Red-Eye Reduction');
            } else {
                $data = (string)t('Unknown: ') . $data;
            }
        }
        if ('1020' == $tag) { //Macro
            if (0 == $data) {
                $data = (string)t('Off');
            } elseif (1 == $data) {
                $data = (string)t('On');
            } else {
                $data = (string)t('Unknown: ') . $data;
            }
        }
        if ('1021' == $tag) { //FocusMode
            if (0 == $data) {
                $data = (string)t('Auto');
            } elseif (1 == $data) {
                $data = (string)t('Manual');
            } else {
                $data = (string)t('Unknown: ') . $data;
            }
        }
        if ('1030' == $tag) { //SlowSync
            if (0 == $data) {
                $data = (string)t('Off');
            } elseif (1 == $data) {
                $data = (string)t('On');
            } else {
                $data = (string)t('Unknown: ') . $data;
            }
        }
        if ('1031' == $tag) { //PictureMode
            if (0 == $data) {
                $data = (string)t('Auto');
            } elseif (1 == $data) {
                $data = (string)t('Portrait');
            } elseif (2 == $data) {
                $data = (string)t('Landscape');
            } elseif (4 == $data) {
                $data = (string)t('Sports');
            } elseif (5 == $data) {
                $data = (string)t('Night');
            } elseif (6 == $data) {
                $data = (string)t('Program AE');
            } elseif (256 == $data) {
                $data = (string)t('Aperture Priority AE');
            } elseif (512 == $data) {
                $data = (string)t('Shutter Priority');
            } elseif (768 == $data) {
                $data = (string)t('Manual Exposure');
            } else {
                $data = (string)t('Unknown: ') . $data;
            }
        }
        if ('1100' == $tag) { //ContinuousTakingBracket
            if (0 == $data) {
                $data = (string)t('Off');
            } elseif (1 == $data) {
                $data = (string)t('On');
            } else {
                $data = (string)t('Unknown: ') . $data;
            }
        }
        if ('1300' == $tag) { //BlurWarning
            if (0 == $data) {
                $data = (string)t('No Warning');
            } elseif (1 == $data) {
                $data = (string)t('Warning');
            } else {
                $data = (string)t('Unknown: ') . $data;
            }
        }
        if ('1301' == $tag) { //FocusWarning
            if (0 == $data) {
                $data = (string)t('Auto Focus Good');
            } elseif (1 == $data) {
                $data = (string)t('Out of Focus');
            } else {
                $data = (string)t('Unknown: ') . $data;
            }
        }
        if ('1302' == $tag) { //AEWarning
            if (0 == $data) {
                $data = (string)t('AE Good');
            } elseif (1 == $data) {
                $data = (string)t('Over Exposure');
            } else {
                $data = (string)t('Unknown: ') . $data;
            }
        }
    } elseif ('UNDEFINED' == $type) {
    } else {
        $data = bin2hex($data);
        if (1 == $intel) {
            $data = intel2Moto($data);
        }
    }

    return $data;
}

//=================
// Fujifilm Special data section
//====================================================================
function parseFujifilm($block, &$result)
{

    //if($result['Endien']=="Intel") $intel=1;
    //else $intel=0;
    $intel = 1;

    $model = $result['IFD0']['Model'];

    $place  = 8; //current place
    $offset = 8;

    $num   = bin2hex(substr($block, $place, 4));
    $place += 4;
    if (1 == $intel) {
        $num = intel2Moto($num);
    }
    $result['SubIFD']['MakerNote']['Offset'] = hexdec($num);

    //Get number of tags (2 bytes)
    $num   = bin2hex(substr($block, $place, 2));
    $place += 2;
    if (1 == $intel) {
        $num = intel2Moto($num);
    }
    $result['SubIFD']['MakerNote']['MakerNoteNumTags'] = hexdec($num);

    //loop thru all tags  Each field is 12 bytes
    for ($i = 0; $i < hexdec($num); $i++) {

        //2 byte tag
        $tag   = bin2hex(substr($block, $place, 2));
        $place += 2;
        if (1 == $intel) {
            $tag = intel2Moto($tag);
        }
        $tag_name = lookup_Fujifilm_tag($tag);

        //2 byte type
        $type  = bin2hex(substr($block, $place, 2));
        $place += 2;
        if (1 == $intel) {
            $type = intel2Moto($type);
        }
        lookup_type($type, $size);

        //4 byte count of number of data units
        $count = bin2hex(substr($block, $place, 4));
        $place += 4;
        if (1 == $intel) {
            $count = intel2Moto($count);
        }
        $bytesofdata = $size * hexdec($count);

        //4 byte value of data or pointer to data
        $value = substr($block, $place, 4);
        $place += 4;

        if ($bytesofdata <= 4) {
            $data = $value;
        } else {
            $value = bin2hex($value);
            if (1 == $intel) {
                $value = intel2Moto($value);
            }
            $data = substr($block, hexdec($value) - $offset, $bytesofdata * 2);
        }
        $formated_data = formatFujifilmData($type, $tag, $intel, $data);

        if (1 == $result['VerboseOutput']) {
            $result['SubIFD']['MakerNote'][$tag_name] = $formated_data;
            if ('URATIONAL' == $type || 'SRATIONAL' == $type || 'USHORT' == $type || 'SSHORT' == $type || 'ULONG' == $type || 'SLONG' == $type || 'FLOAT' == $type || 'DOUBLE' == $type) {
                $data = bin2hex($data);
                if (1 == $intel) {
                    $data = intel2Moto($data);
                }
            }
            $result['SubIFD']['MakerNote'][$tag_name . '_Verbose']['RawData'] = $data;
            $result['SubIFD']['MakerNote'][$tag_name . '_Verbose']['Type']    = $type;
            $result['SubIFD']['MakerNote'][$tag_name . '_Verbose']['Bytes']   = $bytesofdata;
        } else {
            $result['SubIFD']['MakerNote'][$tag_name] = $formated_data;
        }
    }
}

<?php defined('SYSPATH') || die('No direct script access.');
//================================================================================================
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
function lookup_Panasonic_tag($tag)
{
    switch ($tag) {
        case '0001': $tag = 'Quality';break;
        case '0002': $tag = 'FirmwareVersion';break;
        case '0003': $tag = 'WhiteBalance';break;
        case '0007': $tag = 'FocusMode';break;
        case '000f': $tag = 'AFMode';break;
        case '001a': $tag = 'ImageStabilizer';break;
        case '001c': $tag = 'MacroMode';break;
        case '001f': $tag = 'ShootingMode';break;
        case '0020': $tag = 'Audio';break;
        case '0021': $tag = 'DataDump';break;
        case '0023': $tag = 'WhiteBalanceBias';break;
        case '0024': $tag = 'FlashBias';break;
        case '0025': $tag = 'SerialNumber';break;
        case '0028': $tag = 'ColourEffect';break;
        case '002a': $tag = 'BurstMode';break;
        case '002b': $tag = 'SequenceNumber';break;
        case '002c': $tag = 'Contrast';break;
        case '002d': $tag = 'NoiseReduction';break;
        case '002e': $tag = 'SelfTimer';break;
        case '0030': $tag = 'Rotation';break;
        case '0032': $tag = 'ColorMode';break;
        case '0036': $tag = 'TravelDay';break;
        
        default: $tag = 'unknown:' . $tag;break;
    }
    
    return $tag;
}

//=================
// Formats Data for the data type
//====================================================================
function formatPanasonicData($type, $tag, $intel, $data)
{
    if ('ASCII' == $type) {
    } elseif ('UBYTE' == $type || 'SBYTE' == $type) {
        $data = bin2hex($data);
        if (1 == $intel) {
            $data = intel2Moto($data);
        }
        $data=hexdec($data);

        if ('000f' == $tag) { //AFMode
            if (256 == $data) {
                $data = '9-area-focusing';
            } elseif (16 == $data) {
                $data = '1-area-focusing';
            } elseif (4096 == $data) {
                $data = (string) t('3-area-focusing (High speed)');
            } elseif (4112 == $data) {
                $data = (string) t('1-area-focusing (High speed)');
            } elseif (16 == $data) {
                $data = (string) t('1-area-focusing');
            } elseif (1 == $data) {
                $data = (string) t('Spot-focusing');
            } else {
                $data = 'Unknown (' . $data . ')';
            }
        }
    } elseif ('URATIONAL' == $type || 'SRATIONAL' == $type) {
        $data = unRational($data, $type, $intel);
    } elseif ('USHORT' == $type || 'SSHORT' == $type || 'ULONG' == $type || 'SLONG' == $type || 'FLOAT' == $type || 'DOUBLE' == $type) {
        $data = rational($data, $type, $intel);
        
        if ('0001' == $tag) { //Image Quality
            if (2 == $data) {
                $data = (string) t('High');
            } elseif (3 == $data) {
                $data = (string) t('Standard');
            } elseif (6 == $data) {
                $data = (string) t('Very High');
            } elseif (7 == $data) {
                $data = (string) t('RAW');
            } else {
                $data = (string) t('Unknown') . ' (' . $data . ')';
            }
        }
        if ('0003' == $tag) { //White Balance
            if (1 == $data) {
                $data = (string) t('Auto');
            } elseif (2 == $data) {
                $data = (string) t('Daylight');
            } elseif (3 == $data) {
                $data = (string) t('Cloudy');
            } elseif (4 == $data) {
                $data = (string) t('Halogen');
            } elseif (5 == $data) {
                $data = (string) t('Manual');
            } elseif (8 == $data) {
                $data = (string) t('Flash');
            } elseif (10 == $data) {
                $data = (string) t('Black and White');
            } elseif (11 == $data) {
                $data = (string) t('Manual');
            } else {
                $data = (string) t('Unknown') . ' (' . $data . ')';
            }
        }
        if ('0007' == $tag) { //Focus Mode
            if (1 == $data) {
                $data = (string) t('Auto');
            } elseif (2 == $data) {
                $data = (string) t('Manual');
            } elseif (4 == $data) {
                $data = (string) t('Auto, Focus button');
            } elseif (5 == $data) {
                $data = (string) t('Auto, Continuous');
            } else {
                $data = (string) t('Unknown') . ' (' . $data . ')';
            }
        }
        if ('001a' == $tag) { //Image Stabilizer
            if (2 == $data) {
                $data = (string) t('Mode 1');
            } elseif (3 == $data) {
                $data = (string) t('Off');
            } elseif (4 == $data) {
                $data = (string) t('Mode 2');
            } else {
                $data = (string) t('Unknown') . ' (' . $data . ')';
            }
        }
        if ('001c' == $tag) { //Macro mode
            if (1 == $data) {
                $data = (string) t('On');
            } elseif (2 == $data) {
                $data = (string) t('Off');
            } else {
                $data = (string) t('Unknown') . ' (' . $data . ')';
            }
        }
        if ('001f' == $tag) { //Shooting Mode
            if (1 == $data) {
                $data = (string) t('Normal');
            } elseif (2 == $data) {
                $data = (string) t('Portrait');
            } elseif (3 == $data) {
                $data = (string) t('Scenery');
            } elseif (4 == $data) {
                $data = (string) t('Sports');
            } elseif (5 == $data) {
                $data = (string) t('Night Portrait');
            } elseif (6 == $data) {
                $data = (string) t('Program');
            } elseif (7 == $data) {
                $data = (string) t('Aperture Priority');
            } elseif (8 == $data) {
                $data = (string) t('Shutter Priority');
            } elseif (9 == $data) {
                $data = (string) t('Macro');
            } elseif (11 == $data) {
                $data = (string) t('Manual');
            } elseif (13 == $data) {
                $data = (string) t('Panning');
            } elseif (14 == $data) {
                $data = (string) t('Simple');
            } elseif (18 == $data) {
                $data = (string) t('Fireworks');
            } elseif (19 == $data) {
                $data = (string) t('Party');
            } elseif (20 == $data) {
                $data = (string) t('Snow');
            } elseif (21 == $data) {
                $data = (string) t('Night Scenery');
            } elseif (22 == $data) {
                $data = (string) t('Food');
            } elseif (23 == $data) {
                $data = (string) t('Baby');
            } elseif (27 == $data) {
                $data = (string) t('High Sensitivity');
            } elseif (29 == $data) {
                $data = (string) t('Underwater');
            } elseif (33 == $data) {
                $data = (string) t('Pet');
            } else {
                $data = (string) t('Unknown') . ' (' . $data . ')';
            }
        }
        if ('0020' == $tag) { //Audio
            if (1 == $data) {
                $data = (string) t('Yes');
            } elseif (2 == $data) {
                $data = (string) t('No');
            } else {
                $data = (string) t('Unknown') . ' (' . $data . ')';
            }
        }
        if ('0023' == $tag) { //White Balance Bias
            $data= $data . ' EV';
        }
        if ('0024' == $tag) { //Flash Bias
            $data = $data;
        }
        if ('0028' == $tag) { //Colour Effect
            if (1 == $data) {
                $data = (string) t('Off');
            } elseif (2 == $data) {
                $data = (string) t('Warm');
            } elseif (3 == $data) {
                $data = (string) t('Cool');
            } elseif (4 == $data) {
                $data = (string) t('Black and White');
            } elseif (5 == $data) {
                $data = (string) t('Sepia');
            } else {
                $data = (string) t('Unknown') . ' (' . $data . ')';
            }
        }
        if ('002a' == $tag) { //Burst Mode
            if (0 == $data) {
                $data = (string) t('Off');
            } elseif (1 == $data) {
                $data = (string) t('Low/High Quality');
            } elseif (2 == $data) {
                $data = (string) t('Infinite');
            } else {
                $data = (string) t('Unknown') . ' (' . $data . ')';
            }
        }
        if ('002c' == $tag) { //Contrast
            if (0 == $data) {
                $data = (string) t('Standard');
            } elseif (1 == $data) {
                $data = (string) t('Low');
            } elseif (2 == $data) {
                $data = (string) t('High');
            } else {
                $data = (string) t('Unknown') . ' (' . $data . ')';
            }
        }
        if ('002d' == $tag) { //Noise Reduction
            if (0 == $data) {
                $data = (string) t('Standard');
            } elseif (1 == $data) {
                $data = (string) t('Low');
            } elseif (2 == $data) {
                $data = (string) t('High');
            } else {
                $data = (string) t('Unknown') . ' (' . $data . ')';
            }
        }
        if ('002e' == $tag) { //Self Timer
            if (1 == $data) {
                $data = (string) t('Off');
            } elseif (2 == $data) {
                $data = (string) t('10s');
            } elseif (3 == $data) {
                $data = (string) t('2s');
            } else {
                $data = (string) t('Unknown') . ' (' . $data . ')';
            }
        }
        if ('0030' == $tag) { //Rotation
            if (1 == $data) {
                $data = (string) t('Horizontal (normal)');
            } elseif (6 == $data) {
                $data = (string) t('Rotate 90 CW');
            } elseif (8 == $data) {
                $data = (string) t('Rotate 270 CW');
            } else {
                $data = (string) t('Unknown') . ' (' . $data . ')';
            }
        }
        if ('0032' == $tag) { //Color Mode
            if (0 == $data) {
                $data = (string) t('Normal');
            } elseif (1 == $data) {
                $data = (string) t('Natural');
            } else {
                $data = (string) t('Unknown') . ' (' . $data . ')';
            }
        }
        if ('0036' == $tag) { //Travel Day
            $data=$data;
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
// Panasonic Special data section
//====================================================================
function parsePanasonic($block, &$result)
{
        
    //if($result['Endien']=="Intel") $intel=1;
    //else $intel=0;
    $intel=1;
    
    $model = $result['IFD0']['Model'];

    $place=8; //current place
    $offset=8;
    
    
    $num = bin2hex(substr($block, $place, 4));
    $place+=4;
    if (1 == $intel) {
        $num = intel2Moto($num);
    }
    $result['SubIFD']['MakerNote']['Offset'] = hexdec($num);
    
    //Get number of tags (2 bytes)
    $num = bin2hex(substr($block, $place, 2));
    $place+=2;
    if (1 == $intel) {
        $num = intel2Moto($num);
    }
    $result['SubIFD']['MakerNote']['MakerNoteNumTags'] = hexdec($num);
    
    //loop thru all tags  Each field is 12 bytes
    for ($i=0;$i<hexdec($num);$i++) {
        
        //2 byte tag
        $tag = bin2hex(substr($block, $place, 2));
        $place+=2;
        if (1 == $intel) {
            $tag = intel2Moto($tag);
        }
        $tag_name = lookup_Panasonic_tag($tag);
        
        //2 byte type
        $type = bin2hex(substr($block, $place, 2));
        $place+=2;
        if (1 == $intel) {
            $type = intel2Moto($type);
        }
        lookup_type($type, $size);
        
        //4 byte count of number of data units
        $count = bin2hex(substr($block, $place, 4));
        $place+=4;
        if (1 == $intel) {
            $count = intel2Moto($count);
        }
        $bytesofdata = $size*hexdec($count);
        
        //4 byte value of data or pointer to data
        $value = substr($block, $place, 4);
        $place+=4;

        
        if ($bytesofdata<=4) {
            $data = $value;
        } else {
            $value = bin2hex($value);
            if (1 == $intel) {
                $value = intel2Moto($value);
            }
            $data = substr($block, hexdec($value)-$offset, $bytesofdata*2);
        }
        $formated_data = formatPanasonicData($type, $tag, $intel, $data);
        
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

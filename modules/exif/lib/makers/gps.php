<?php defined('SYSPATH') || die('No direct script access.');
//================================================================================================
//================================================================================================
/*
    Exifer
    Extracts EXIF information from digital photos.

    Copyright © 2003 Jake Olefsky
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
// Looks up the name of the tag
//====================================================================
function lookup_GPS_tag($tag)
{
    switch ($tag) {
        case '0000':
            $tag = 'Version';
            break;
        case '0001':
            $tag = 'Latitude Reference';
            break;            //north or south
        case '0002':
            $tag = 'Latitude';
            break;                    //dd mm.mm or dd mm ss
        case '0003':
            $tag = 'Longitude Reference';
            break;        //east or west
        case '0004':
            $tag = 'Longitude';
            break;                    //dd mm.mm or dd mm ss
        case '0005':
            $tag = 'Altitude Reference';
            break;            //sea level or below sea level
        case '0006':
            $tag = 'Altitude';
            break;                    //positive rational number
        case '0007':
            $tag = 'Time';
            break;                        //three positive rational numbers
        case '0008':
            $tag = 'Satellite';
            break;                    //text string up to 999 bytes long
        case '0009':
            $tag = 'ReceiveStatus';
            break;                //in progress or interop
        case '000a':
            $tag = 'MeasurementMode';
            break;            //2D or 3D
        case '000b':
            $tag = 'MeasurementPrecision';
            break;        //positive rational number
        case '000c':
            $tag = 'SpeedUnit';
            break;                    //KPH, MPH, knots
        case '000d':
            $tag = 'ReceiverSpeed';
            break;                //positive rational number
        case '000e':
            $tag = 'MovementDirectionRef';
            break;        //true or magnetic north
        case '000f':
            $tag = 'MovementDirection';
            break;            //positive rational number
        case '0010':
            $tag = 'ImageDirectionRef';
            break;            //true or magnetic north
        case '0011':
            $tag = 'ImageDirection';
            break;                //positive rational number
        case '0012':
            $tag = 'GeodeticSurveyData';
            break;            //text string up to 999 bytes long
        case '0013':
            $tag = 'DestLatitudeRef';
            break;            //north or south
        case '0014':
            $tag = 'DestinationLatitude';
            break;        //three positive rational numbers
        case '0015':
            $tag = 'DestLongitudeRef';
            break;            //east or west
        case '0016':
            $tag = 'DestinationLongitude';
            break;        //three positive rational numbers
        case '0017':
            $tag = 'DestBearingRef';
            break;                //true or magnetic north
        case '0018':
            $tag = 'DestinationBearing';
            break;            //positive rational number
        case '0019':
            $tag = 'DestDistanceRef';
            break;            //km, miles, knots
        case '001a':
            $tag = 'DestinationDistance';
            break;        //positive rational number
        case '001b':
            $tag = 'ProcessingMethod';
            break;
        case '001c':
            $tag = 'AreaInformation';
            break;
        case '001d':
            $tag = 'Datestamp';
            break;                    //text string 10 bytes long
        case '001e':
            $tag = 'DifferentialCorrection';
            break;        //integer in range 0-65535

        default:
            $tag = 'unknown:' . $tag;
            break;
    }

    return $tag;
}

//=================
// Formats Data for the data type
//====================================================================
function formatGPSData($type, $tag, $intel, $data)
{
    if ('ASCII' == $type) {
        if ('0001' == $tag || '0003' == $tag) { // Latitude Reference, Longitude Reference
            $data = ($data{1} == $data{2} && $data{1} == $data{3}) ? $data{0} : $data;
        }
    } elseif ('URATIONAL' == $type || 'SRATIONAL' == $type) {
        if ('0002' == $tag || '0004' == $tag || '0007' == $tag) { //Latitude, Longitude, Time
            $datum = [];
            for ($i = 0; $i < strlen($data); $i = $i + 8) {
                array_push($datum, substr($data, $i, 8));
            }
            $hour    = unRational($datum[0], $type, $intel);
            $minutes = unRational($datum[1], $type, $intel);
            $seconds = unRational($datum[2], $type, $intel);
            if ('0007' == $tag) { //Time
                $data = $hour . ':' . $minutes . ':' . $seconds;
            } else {
                $data = $hour + $minutes / 60 + $seconds / 3600;
            }
        } else {
            $data = unRational($data, $type, $intel);

            if ('0006' == $tag) {
                $data .= 'm';
            }
        }
    } elseif ('USHORT' == $type || 'SSHORT' == $type || 'ULONG' == $type || 'SLONG' == $type || 'FLOAT' == $type || 'DOUBLE' == $type) {
        $data = rational($data, $type, $intel);
    } elseif ('UNDEFINED' == $type) {
    } elseif ('UBYTE' == $type) {
        $data = bin2hex($data);
        if (1 == $intel) {
            $num = intel2Moto($data);
        }

        if ('0000' == $tag) { // VersionID
            $data = hexdec(substr($data, 0, 2)) . '.' . hexdec(substr($data, 2, 2)) . '.' . hexdec(substr($data, 4, 2)) . '.' . hexdec(substr($data, 6, 2));
        } elseif ('0005' == $tag) { // Altitude Reference
            if ('00000000' == $data) {
                $data = '+';
            } elseif ('01000000' == $data) {
                $data = '-';
            }
        }
    } else {
        $data = bin2hex($data);
        if (1 == $intel) {
            $data = intel2Moto($data);
        }
    }

    return $data;
}

//=================
// GPS Special data section
// Useful websites
// http://drewnoakes.com/code/exif/sampleOutput.html
// http://www.geosnapper.com
//====================================================================
function parseGPS($block, &$result, $offset, $seek, $globalOffset)
{
    if ('Intel' == $result['Endien']) {
        $intel = 1;
    } else {
        $intel = 0;
    }

    $v = fseek($seek, $globalOffset + $offset);  //offsets are from TIFF header which is 12 bytes from the start of the file
    if (-1 == $v) {
        $result['Errors'] = $result['Errors']++;
    }

    $num = bin2hex(fread($seek, 2));
    if (1 == $intel) {
        $num = intel2Moto($num);
    }
    $num                      = hexdec($num);
    $result['GPS']['NumTags'] = $num;

    if (0 == $num) {
        return;
    }

    $block = fread($seek, $num * 12);
    $place = 0;

    //loop thru all tags  Each field is 12 bytes
    for ($i = 0; $i < $num; $i++) {
        //2 byte tag
        $tag   = bin2hex(substr($block, $place, 2));
        $place += 2;
        if (1 == $intel) {
            $tag = intel2Moto($tag);
        }
        $tag_name = lookup_GPS_tag($tag);

        //2 byte datatype
        $type  = bin2hex(substr($block, $place, 2));
        $place += 2;
        if (1 == $intel) {
            $type = intel2Moto($type);
        }
        lookup_type($type, $size);

        //4 byte number of elements
        $count = bin2hex(substr($block, $place, 4));
        $place += 4;
        if (1 == $intel) {
            $count = intel2Moto($count);
        }
        $bytesofdata = $size * hexdec($count);

        //4 byte value or pointer to value if larger than 4 bytes
        $value = substr($block, $place, 4);
        $place += 4;
        if ($bytesofdata <= 4) {
            $data = $value;
        } else {
            if (false !== strpos('unknown', $tag_name) || $bytesofdata > 1024) {
                $result['Errors'] = $result['Errors']++;
                $data             = '';
                $type             = 'ASCII';
            } else {
                $value = bin2hex($value);
                if (1 == $intel) {
                    $value = intel2Moto($value);
                }
                $v = fseek($seek, $globalOffset + hexdec($value));  //offsets are from TIFF header which is 12 bytes from the start of the file
                if (0 == $v) {
                    $data = fread($seek, $bytesofdata);
                } else {
                    $result['Errors'] = $result['Errors']++;
                    $data             = '';
                    $type             = 'ASCII';
                }
            }
        }
        if (1 == $result['VerboseOutput']) {
            $result['GPS'][$tag_name]                         = formatGPSData($type, $tag, $intel, $data);
            $result['GPS'][$tag_name . '_Verbose']['RawData'] = bin2hex($data);
            $result['GPS'][$tag_name . '_Verbose']['Type']    = $type;
            $result['GPS'][$tag_name . '_Verbose']['Bytes']   = $bytesofdata;
        } else {
            $result['GPS'][$tag_name] = formatGPSData($type, $tag, $intel, $data);
        }
    }
}

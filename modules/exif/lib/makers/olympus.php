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
function lookup_Olympus_tag($tag)
{
    switch ($tag) {
        case '0200':
            $tag = 'SpecialMode';
            break;
        case '0201':
            $tag = 'JpegQual';
            break;
        case '0202':
            $tag = 'Macro';
            break;
        case '0203':
            $tag = 'Unknown1';
            break;
        case '0204':
            $tag = 'DigiZoom';
            break;
        case '0205':
            $tag = 'Unknown2';
            break;
        case '0206':
            $tag = 'Unknown3';
            break;
        case '0207':
            $tag = 'SoftwareRelease';
            break;
        case '0208':
            $tag = 'PictInfo';
            break;
        case '0209':
            $tag = 'CameraID';
            break;
        case '0f00':
            $tag = 'DataDump';
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
function formatOlympusData($type, $tag, $intel, $data)
{
    if ('ASCII' == $type) {
    } elseif ('URATIONAL' == $type || 'SRATIONAL' == $type) {
        $data = unRational($data, $type, $intel);
        if (1 == $intel) {
            $data = intel2Moto($data);
        }

        if ('0204' == $tag) { //DigitalZoom
            $data = $data . 'x';
        }
        if ('0205' == $tag) { //Unknown2
        }
    } elseif ('USHORT' == $type || 'SSHORT' == $type || 'ULONG' == $type || 'SLONG' == $type || 'FLOAT' == $type || 'DOUBLE' == $type) {
        $data = rational($data, $type, $intel);

        if ('0201' == $tag) { //JPEGQuality
            if (1 == $data) {
                $data = 'SQ';
            } elseif (2 == $data) {
                $data = 'HQ';
            } elseif (3 == $data) {
                $data = 'SHQ';
            } else {
                $data = (string)t('Unknown') . ': ' . $data;
            }
        }
        if ('0202' == $tag) { //Macro
            if (0 == $data) {
                $data = 'Normal';
            } elseif (1 == $data) {
                $data = 'Macro';
            } else {
                $data = (string)t('Unknown') . ': ' . $data;
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

//==============================================================================
// Olympus Special data section
// - Updated by Zenphoto for new header tag in E-410/E-510/E-3 cameras. 2/24/2008
//==============================================================================
function parseOlympus($block, &$result, $seek, $globalOffset)
{
    if ('Intel' == $result['Endien']) {
        $intel = 1;
    } else {
        $intel = 0;
    }

    $model = $result['IFD0']['Model'];

    // New header for new DSLRs - Check for it because the
    // number of bytes that count the IFD fields differ in each case.
    // Fixed by Zenphoto 2/24/08
    $new = false;
    if ("OLYMPUS\x00" == substr($block, 0, 8)) {
        $new = true;
    } elseif ("OLYMP\x00\x01" == substr($block, 0, 7)
              || "OLYMP\x00\x02" == substr($block, 0, 7)) {
        $new = false;
    } else {
        // Header does not match known Olympus headers.
        // This is not a valid OLYMPUS Makernote.
        return false;
    }

    // Offset of IFD entry after Olympus header.
    $place  = 8;
    $offset = 8;

    // Get number of tags (1 or 2 bytes, depending on New or Old makernote)
    $countfieldbits = $new ? 1 : 2;
    // New makernote repeats 1-byte value twice, so increment $place by 2 in either case.
    $num   = bin2hex(substr($block, $place, $countfieldbits));
    $place += 2;
    if (1 == $intel) {
        $num = intel2Moto($num);
    }
    $ntags                                             = hexdec($num);
    $result['SubIFD']['MakerNote']['MakerNoteNumTags'] = $ntags;

    //loop thru all tags  Each field is 12 bytes
    for ($i = 0; $i < $ntags; $i++) {
        //2 byte tag
        $tag   = bin2hex(substr($block, $place, 2));
        $place += 2;
        if (1 == $intel) {
            $tag = intel2Moto($tag);
        }
        $tag_name = lookup_Olympus_tag($tag);

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
            $v = fseek($seek, $globalOffset + hexdec($value));  //offsets are from TIFF header which is 12 bytes from the start of the file
            if (isset($GLOBALS['exiferFileSize']) && 0 == $v && $bytesofdata < $GLOBALS['exiferFileSize']) {
                $data = fread($seek, $bytesofdata);
            } else {
                $result['Errors'] = $result['Errors']++;
                $data             = '';
            }
        }
        $formated_data = formatOlympusData($type, $tag, $intel, $data);

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

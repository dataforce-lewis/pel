<?php

/*  PEL: PHP EXIF Library.  A library with support for reading and
 *  writing all EXIF headers of JPEG images using PHP.
 *
 *  Copyright (C) 2004  Martin Geisler <gimpster@users.sourceforge.net>
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program in the file COPYING; if not, write to the
 *  Free Software Foundation, Inc., 59 Temple Place, Suite 330,
 *  Boston, MA 02111-1307 USA
 */

/* $Id$ */


/**
 * Classes used to hold data for EXIF tags of format undefined.
 *
 * This file contains the base class {@link PelExifEntryUndefined} and
 * the subclasses {@link PelExifEntryUserComment} which should be used
 * to manage the {@link PelExifTag::USER_COMMENT} tag, and {@link
 * PelExifEntryVersion} which is used to manage entries with version
 * information.
 *
 * @author Martin Geisler <gimpster@users.sourceforge.net>
 * @version $Revision$
 * @date $Date$
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public
 * License (GPL)
 * @package PEL
 * @subpackage EXIF
 */

/** Class definition of {@link PelException}. */
include_once('PelException.php');
/** Class definition of {@link PelExifEntry}. */
include_once('PelExifEntry.php');


/**
 * Class for holding data of any kind.
 *
 * This class can hold bytes of undefined format.
 *
 * @author Martin Geisler <gimpster@users.sourceforge.net>
 * @package PEL
 * @subpackage EXIF
 */
class PelExifEntryUndefined extends PelExifEntry {

  /**
   * Make a new PelExifEntry that can hold undefined data.
   *
   * @param PelExifTag the tag which this entry represents.  This
   * should be one of the constants defined in {@link PelExifTag},
   * e.g., {@link PelExifTag::SCENE_TYPE}, {@link
   * PelExifTag::MAKER_NOTE} or any other tag with format {@link
   * PelExifFormat::UNDEFINED}.
   *
   * @param string the data that this entry will be holding.  Since
   * the format is undefined, no checking will be done on the data.
   */
  function __construct($tag, $data = '') {
    $this->tag        = $tag;
    $this->format     = PelExifFormat::UNDEFINED;
    $this->setUndefined($data);
  }


  /**
   * Set the data of this undefined entry.
   *
   * @param string the data that this entry will be holding.  Since
   * the format is undefined, no checking will be done on the data.
   *
   * @todo find a better name... maybe figure out a way to have all
   * setters named setValue?
   */
  function setUndefined($data) {
    $this->components = strlen($data);
    $this->bytes      = $data;
  }


  /**
   * Get the value of this entry as text.
   *
   * The value will be returned in a format suitable for presentation.
   *
   * @param boolean some values can be returned in a long or more
   * brief form, and this parameter controls that.
   *
   * @return string the value as text.
   */
  function getText($brief = false) {
    switch ($this->tag) {
    case PelExifTag::FILE_SOURCE:
      //CC (e->components, 1, v);
      switch ($this->bytes{0}) {
      case 0x03:
        return 'DSC';
      default:
        return sprintf('0x%02X', $this->bytes{0});
      }
   
    case PelExifTag::COMPONENTS_CONFIGURATION:
      //CC (e->components, 4, v);
      $v = '';
      for ($i = 0; $i < 4; $i++) {
        switch (ord($this->bytes{$i})) {
        case 0:
          $v .= '-';
          break;
        case 1:
          $v .= 'Y';
          break;
        case 2:
          $v .= 'Cb';
          break;
        case 3:
          $v .= 'Cr';
          break;
        case 4:
          $v .= 'R';
          break;
        case 5:
          $v .= 'G';
          break;
        case 6:
          $v .= 'B';
          break;
        default:
          $v .= 'reserved';
          break;
        }
        if ($i < 3) $v .= ' ';
      }
      return $v;

    case PelExifTag::MAKER_NOTE:
      // TODO: handle maker notes.
      return $this->components . ' bytes unknown data';

    default:
      return '(undefined)';
    }
  }

}


/**
 * Class for a user comment.
 *
 * This class is used to hold user comments, which can come in several
 * different character encodings.  The EXIF standard specifies a
 * certain format of the {@link PelExifTag::USER_COMMENT user comment
 * tag}, and this class will make sure that the format is kept.
 *
 * The most basic use of this class simply stores an ASCII encoded
 * string for later retrieval using {@link getComment}:
 *
 * <code>
 * $entry = new PelExifEntryUserComment('An ASCII string');
 * echo $entry->getComment();
 * </code>
 *
 * The string can be encoded with a different encoding, and if so, the
 * encoding must be given using the second argument.  The EXIF
 * standard specifies three known encodings: 'ASCII', 'JIS', and
 * 'Unicode'.  If the user comment is encoded using a character
 * encoding different from the tree known encodings, then the empty
 * string should be passed as encoding, thereby making the encoding
 * undefined.
 *
 * @author Martin Geisler <gimpster@users.sourceforge.net>
 * @package PEL
 * @subpackage EXIF
 */
class PelExifEntryUserComment extends PelExifEntryUndefined {

  /**
   * The user comment.
   *
   * @var string
   */
  private $comment;

  /**
   * The encoding.
   *
   * This should be one of 'ASCII', 'JIS', 'Unicode', or ''.
   *
   * @var string
   */

  /**
   * Make a new entry for holding a user comment.
   *
   * @param string the new user comment.
   *
   * @param string the encoding of the comment.  This should be either
   * 'ASCII', 'JIS', 'Unicode', or the empty string specifying an
   * undefined encoding.
   */
  function __construct($comment = '', $encoding = 'ASCII') {
    parent::__construct(PelExifTag::USER_COMMENT);
    $this->setComment($comment, $encoding);
  }

  
  /**
   * Set the user comment.
   *
   * @param string the new user comment.
   *
   * @param string the encoding of the comment.  This should be either
   * 'ASCII', 'JIS', 'Unicode', or the empty string specifying an
   * unknown encoding.
   */
  function setComment($comment = '', $encoding = 'ASCII') {
    $this->comment  = $comment;
    $this->encoding = $encoding;
    $this->setUndefined(str_pad($encoding, 8, chr(0)) . $comment);
  }


  /**
   * Returns the user comment.
   *
   * The comment is returned with the same character encoding as when
   * it was set using {@link setComment} or {@link __construct the
   * constructor}.
   *
   * @return string the user comment.
   */
  function getComment() {
    return $this->comment;
  }


  /**
   * Returns the encoding.
   *
   * @return string the encoding of the user comment.
   */
  function getEncoding() {
    return $this->encoding;
  }


  /**
   * Returns the user comment.
   *
   * @return string the user comment.
   */
  function getText($brief = false) {
    return $this->comment;
  }

}


/**
 * Class to hold version information.
 *
 * There are three EXIF entries that hold version information: the
 * {@link PelExifTag::EXIF_VERSION}, {@link
 * PelExifTag::FLASH_PIX_VERSION}, and {@link
 * PelExifTag::INTEROPERABILITY_VERSION} tags.  This class manages
 * those tags.
 *
 * The class is used in a very straight-forward way:
 * <code>
 * $entry = new PelExifEntryVersion(PelExifTag::EXIF_VERSION, 2.2);
 * </code>
 * This creates an entry for an file complying to the EXIF 2.2
 * standard.  It is easy to test for standards level of an unknown
 * entry:
 * <code>
 * if ($entry->getTag() == PelExifTag::EXIF_VERSION &&
 *     $entry->getVersion() > 2.0) {
 *   echo 'Recent EXIF version.';
 * }
 * </code>
 *
 * @author Martin Geisler <gimpster@users.sourceforge.net>
 * @package PEL
 * @subpackage EXIF
 */
class PelExifEntryVersion extends PelExifEntryUndefined {

  /**
   * The version held by this entry.
   *
   * @var float
   */
  private $version;


  /**
   * Make a new entry for holding a version.
   *
   * @param PelExifTag the tag.  This should be one of {@link
   * PelExifTag::EXIF_VERSION}, {@link PelExifTag::FLASH_PIX_VERSION},
   * or {@link PelExifTag::INTEROPERABILITY_VERSION}.
   *
   * @param float the version.  The size of the entries leave room for
   * exactly four digits: two digits on either side of the decimal
   * point.
   */
  function __construct($tag, $version = 0.0) {
    parent::__construct($tag);
    $this->setVersion($version);
  }


  /**
   * Set the version held by this entry.
   *
   * @param float the version.  The size of the entries leave room for
   * exactly four digits: two digits on either side of the decimal
   * point.
   */
  function setVersion($version = 0.0) {
    $this->version = $version;
    $major = floor($version);
    $minor = ($version - $major)*100;
    $this->setUndefined(sprintf('%02d%02d', $major, $minor));
  }


  /**
   * Return the version held by this entry.
   *
   * @return float the version.  This will be the same as the value
   * given to {@link setVersion} or {@link __construct the
   * constructor}.
   */
  function getVersion() {
    return $this->version;
  }

 
  /**
   * Return a text string with the version.
   *
   * @param boolean controls if the output should be brief.  Brief
   * output omits the word 'Version' so the result is just 'Exif x.y'
   * instead of 'Exif Version x.y' if the entry holds information
   * about the EXIF version --- the output for FlashPix is similar.
   *
   * @return string the version number with the type of the tag,
   * either 'Exif' or 'FlashPix'.
   */
  function getText($brief = false) {
    if ($brief)
      $v = '';
    else
      $v = 'Version ';

    if ($this->tag == PelExifTag::EXIF_VERSION)
      return 'Exif ' . $v . $this->version;
    
    if ($this->tag == PelExifTag::FLASH_PIX_VERSION)
      return 'FlashPix ' . $v . $this->version;

    if ($this->tag == PelExifTag::INTEROPERABILITY_VERSION)
      return 'Interoperability ' . $v . $this->version;

    return $v. $this->version;
  }

}

?>
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
 * Classes for dealing with EXIF data.
 *
 * @author Martin Geisler <gimpster@users.sourceforge.net>
 * @version $Revision$
 * @date $Date$
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public
 * License (GPL)
 * @package PEL
 */

/**#@+ Required class definitions. */
require_once('PelJpegContent.php');
require_once('PelException.php');
require_once('PelFormat.php');
require_once('PelEntry.php');
require_once('PelTiff.php');
require_once('PelIfd.php');
require_once('PelTag.php');
/**#@-*/


/**
 * Exception throw if invalid EXIF data is found.
 *
 * @author Martin Geisler <gimpster@users.sourceforge.net>
 * @package PEL
 * @subpackage Exception
 */
class PelExifInvalidDataException extends PelException {}

/**
 * Class representing EXIF data.
 *
 * EXIF data resides as {@link PelJpeg data} in a {@link
 * PelJpegSection JPEG section} and consists of a header followed by a
 * number of {@link PelJpegIfd IFDs}.
 *
 * @author Martin Geisler <gimpster@users.sourceforge.net>
 * @package PEL
 */
class PelExif extends PelJpegContent {

  /**
   * EXIF header.
   *
   * The EXIF data must start with these six bytes to be considered
   * valid.
   */
  const EXIF_HEADER = "Exif\0\0";

  /* The PelTiff contained within. */
  private $tiff = null;
  private $size = 0;


  /**
   * Parse EXIF data.
   *
   * This will construct a new object containing EXIF data as a {@link
   * PelTiff} object.  This object can be accessed with the {@link
   * getTiff()} method.
   */
  function __construct(PelDataWindow $d) {
    Pel::debug('Parsing %d bytes of EXIF data...', $d->getSize());
    $this->size = $d->getSize();

    /* There must be at least 6 bytes for the EXIF header. */
    if ($d->getSize() < 6)
      throw new PelInvalidDataException('Expected at least 6 bytes of EXIF ' .
                                        'data, found just %d bytes.',
                                        $d->getSize());
    
    /* Verify the EXIF header */
    if ($d->strcmp(0, self::EXIF_HEADER)) {
      $d->setWindowStart(6);
    } else {
      throw new PelExifInvalidDataException('EXIF header not found.');
    }

    /* The rest of the data is TIFF data. */
    $this->tiff = new PelTiff($d);
  }

  function getSize() {
    return $this->size;
  }

  /**
   * Get the underlying TIFF object.
   *
   * The actual EXIF data is stored in a {@link PelTiff} object, and
   * this method provides access to it.
   *
   * @return PelTiff the TIFF object with the EXIF data.
   */
  function getTiff() {
    return $this->tiff;
  }

  /**
   * Produce bytes for this object.
   *
   * @return string bytes representing this object.  These bytes will
   * match the bytes given to {@link __construct the constructor}.
   */
  function getBytes() {
    return self::EXIF_HEADER . $this->tiff->getbytes();
  }
  
  /**
   * Return a string representation of this object.
   *
   * @return string a string describing this object.  This is mostly
   * useful for debugging.
   */
  function __toString() {
    return sprintf("Dumping %d bytes of EXIF data...\n%s",
                   $this->size,
                   $this->tiff->__toString());
  }

}



?>
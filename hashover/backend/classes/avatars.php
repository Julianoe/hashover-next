<?php namespace HashOver;

// Copyright (C) 2015-2018 Jacob Barkdull
// This file is part of HashOver.
//
// HashOver is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as
// published by the Free Software Foundation, either version 3 of the
// License, or (at your option) any later version.
//
// HashOver is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Affero General Public License for more details.
//
// You should have received a copy of the GNU Affero General Public License
// along with HashOver.  If not, see <http://www.gnu.org/licenses/>.


class Avatars
{
	protected $setup;
	protected $isVector;
	protected $gravatar;
	protected $iconSize;
	protected $avatar;
	protected $png;
	protected $svg;
	protected $fallback;

	// Supported icon sizes
	protected $iconSizes = array (45, 64, 128, 256, 512);

	// Initial setup
	public function __construct (Setup $setup)
	{
		// Store parameters as properties
		$this->setup = $setup;

		// Whether icon is vector based on setting
		$this->isVector = ($setup->imageFormat === 'svg');

		// Get HTTPS status
		$this->isHTTPS = $setup->isHTTPS ();

		// Icon setup
		$this->iconSetup ();
	}

	// Gets default avatar size closest to the given size
	protected function closestSize ($size = 45)
	{
		// Current supported size
		$closest = $this->iconSizes[0];

		// Find the closest size
		for ($i = 0, $il = count ($this->iconSizes); $i < $il; $i++) {
			// Check if the size is too small
			if ($size > $closest) {
				// If so, increase closest size
				$closest = $this->iconSizes[$i];
			} else {
				// If not, end the loop
				break;
			}
		}

		return $closest;
	}

	// Sets up avatar
	protected function iconSetup ($force_size = false)
	{
		// Decide desired size of icon
		$size = $force_size ? $force_size : $this->setup->iconSize;

		// Deside whether icon is vector
		$size = $this->isVector ? 256 : $size;

		// Set icon size to the closest supported size
		$this->iconSize = $this->closestSize ($size);

		// Default avatar file names
		$avatar = $this->setup->httpImages . '/avatar';
		$this->png = $avatar . '-' . $this->iconSize . '.png';
		$this->svg = $avatar . '.svg';

		// Set avatar property to appropriate type
		$this->avatar = $this->isVector ? $this->svg : $this->png;

		// Use HTTPS if this file is requested with HTTPS
		$this->http = ($this->isHTTPS ? 'https' : 'http') . '://';
		$this->subdomain = $this->isHTTPS ? 'secure' : 'www';

		// Check if avatar is set to custom
		if ($this->setup->gravatarDefault === 'custom') {
			// If so, direct 404s to local PNG avatar image
			$this->fallback = urlencode ($this->png);
		} else {
			// If not, direct 404s to a themed default
			$this->fallback = $this->setup->gravatarDefault;
		}

		// Gravatar URL
		$this->gravatar  = $this->http . $this->subdomain;
		$this->gravatar .= '.gravatar.com/avatar/';
	}

	// Attempt to get Gravatar avatar image
	public function getGravatar ($hash, $abs = false, $size = false)
	{
		// Perform setup again if the size doesn't match
		if ($size !== false and $size !== $this->iconSize) {
			$this->iconSetup ($size);
		}

		// If no hash is given, return the default avatar
		if (empty ($hash)) {
			// Return absolute path if requested
			if ($abs === true) {
				return $this->setup->absolutePath . $this->avatar;
			}

			// Otherwise, return avatar path as-is
			return $this->avatar;
		}

		// Gravatar URL
		$gravatar  = $this->gravatar . $hash . '.png?r=pg';
		$gravatar .= '&amp;s=' . $this->iconSize;
		$gravatar .= '&amp;d=' . $this->fallback;

		// Force Gravatar default avatar if enabled
		if ($this->setup->gravatarForce === true) {
			$gravatar .= '&amp;f=y';
		}

		// Redirect Gravatar image
		return $gravatar;
	}
}

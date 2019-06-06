<?php
/**
*
* @package phpBB Extension - Addon for Contact Admin
* @copyright (c) 2019 Sheer
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/
namespace sheer\contactadmin_addon\migrations;

class contactadmin_addon_1_0_0 extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return;
	}

	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v310\dev');
	}

	public function update_schema()
	{
		return array(
		);
	}

	public function revert_schema()
	{
		return array(
		);
	}

	public function update_data()
	{
		return array(
			// Current version
			array('config.add', array('contactadmin_addon_version', '1.0.0')),
		);
	}
}

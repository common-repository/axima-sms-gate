<?php
/**
 * @author Tomáš Blatný
 */

namespace Pays\SmsGate;


class Validators
{

	public static function validateNumber($number)
	{
		return (bool) preg_match('~^(\+|00)?[0-9]+$~', (string) $number);
	}

}

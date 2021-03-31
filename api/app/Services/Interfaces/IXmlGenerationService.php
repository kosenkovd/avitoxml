<?php

namespace App\Services\Interfaces;

interface IXmlGenerationService
{
	/**
	 * Generates Avito XML for specified spreadsheet.
	 *
	 * @param string $spreadsheetId spreadsheet id.
	 * @param string $targetSheet   sheet name to generate XML from.
	 * @param int    $adLimit       max ads
	 * @return string generated XML.
	 * @throws \Exception
	 */
	public function generateAvitoXML(string $spreadsheetId, string $targetSheet, int $adsLimit): string;

	/**
	 * Generates Yandex XML for specified spreadsheet.
	 *
	 * @param string $spreadsheetId spreadsheet id.
	 * @param string $targetSheet   sheet name to generate XML from.
	 * @param int    $adLimit
	 * @return string generated XML.
	 * @throws \Exception
	 */
	public function generateYandexXML(string $spreadsheetId, string $targetSheet, int $adsLimit): string;

	/**
	 * Generates Ula XML for specified spreadsheet.
	 *
	 * @param string $spreadsheetId spreadsheet id.
	 * @param string $targetSheet   sheet name to generate XML from.
	 * @param int    $adLimit
	 * @return string generated XML.
	 * @throws \Exception
	 */
	public function generateUlaXML(string $spreadsheetId, string $targetSheet, int $adsLimit): string;
}

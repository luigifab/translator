<?xml version="1.0" encoding="utf-8"?>
<!--
 * Created L/10/12/2012
 * Updated J/25/04/2019
 *
 * Copyright 2012-2024 | Fabrice Creuzot (luigifab) <code~luigifab~fr>
 * https://www.luigifab.fr/
 *
 * This program is free software, you can redistribute it or modify
 * it under the terms of the GNU General Public License (GPL) as published
 * by the free software foundation, either version 2 of the license, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but without any warranty, without even the implied warranty of
 * merchantability or fitness for a particular purpose. See the
 * GNU General Public License (GPL) for more details.
-->

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:output method="text" indent="no" omit-xml-declaration="yes" />
	<xsl:template match="/">
		<xsl:variable name="quote">"</xsl:variable>
		<xsl:for-each select="//*[contains(@translate,'label')]/label | //*[contains(@translate,'tooltip')]/tooltip | //*[contains(@translate,'comment')]/comment | //*[contains(@translate,'title')]/title | //*[contains(@translate,'description')]/description">
			<xsl:value-of select="concat(translate(., $quote, '`'), '§')" />
		</xsl:for-each>
	</xsl:template>
</xsl:stylesheet>
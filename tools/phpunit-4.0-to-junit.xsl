<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:template match="/">
        <xsl:element name="testsuites">
            <xsl:copy>
                <xsl:apply-templates select="//testsuite[@file]"/>
            </xsl:copy>
        </xsl:element>
    </xsl:template>
    <xsl:template match="@*|node()">
        <xsl:copy>
            <xsl:apply-templates select="@*|node()"/>
        </xsl:copy>
    </xsl:template>
    <xsl:template match="@time">
        <xsl:attribute name="time">
            <xsl:value-of select="format-number(number(.),'#.###')"/>
        </xsl:attribute>
    </xsl:template>
    <xsl:template match="@assertions[parent::testsuite]"/>
    <xsl:template match="@warnings[parent::testsuite]"/>
    <xsl:template match="@class[parent::testcase]"/>
    <xsl:template match="@file[parent::testcase]"/>
    <xsl:template match="@line[parent::testcase]"/>
    <xsl:template match="@assertions[parent::testcase]"/>
</xsl:stylesheet>

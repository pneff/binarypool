<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
        version="1.0">

    <xsl:output encoding="UTF-8" indent="yes" method="xml" />

    <xsl:template match="/">
        <xsl:apply-templates select="/command/status" />
    </xsl:template>
    
    <xsl:template match="status[@type='error']">
        <error>
            <xsl:apply-templates select="@error" />
            <msg>
                <xsl:value-of select="msg"/>
            </msg>
        </error>
    </xsl:template>
    <xsl:template match="status[@type='error']/@error">
        <code><xsl:value-of select="." /></code>
    </xsl:template>
    
    <xsl:template match="status[@method='get']">
        <buckets>
            <xsl:comment>List of defined buckets</xsl:comment>
            <xsl:copy-of select="bucket" />
        </buckets>
    </xsl:template>
    
    <xsl:template match="status[@method='view']">
        <view>
            <xsl:apply-templates select="file" />
        </view>
    </xsl:template>

    <xsl:template match="status[@method='view']/file">
        <asset>
            <id deprecated="true"><xsl:value-of select="@id" /></id>
            <path deprecated="true">
                <xsl:value-of select="." />
                <xsl:text>index.xml</xsl:text>
            </path>
            <asset>
                <xsl:value-of select="." />
                <xsl:text>index.xml</xsl:text>
            </asset>
        </asset>
    </xsl:template>
    
    <xsl:template match="status[@method='post']">
        <saved>
            <asset>
                <xsl:value-of select="asset"/>
            </asset>
        </saved>
    </xsl:template>
    
    <xsl:template match="*" />
</xsl:stylesheet>

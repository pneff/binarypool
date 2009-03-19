<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
        version="1.0">

    <xsl:output encoding="UTF-8" indent="yes" method="xml" />

    <xsl:template match="/">
        <xsl:processing-instruction name="xml">
            <xsl:text>version="1.0" encoding="UTF-8"</xsl:text>
        </xsl:processing-instruction>
        
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
        <xsl:call-template name="clientxsl">
            <xsl:with-param name="stylesheet" select="'buckets.xsl'"/>
        </xsl:call-template>
        <buckets>
            <xsl:comment>List of defined buckets</xsl:comment>
            <xsl:copy-of select="bucket" />
        </buckets>
    </xsl:template>
    
    <xsl:template match="status[@method='getbucket']">
        <xsl:call-template name="clientxsl">
            <xsl:with-param name="stylesheet" select="'bucket.xsl'"/>
        </xsl:call-template>
        <xsl:copy-of select="bucket" />
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
        <xsl:call-template name="clientxsl">
            <xsl:with-param name="stylesheet" select="'post.xsl'"/>
        </xsl:call-template>
        <saved>
            <asset>
                <xsl:value-of select="asset"/>
            </asset>
        </saved>
    </xsl:template>
    
    <xsl:template name="clientxsl">
        <xsl:param name="stylesheet"/>
        <xsl:if test="/command/clientxsl = 1">
            <xsl:processing-instruction name="xml-stylesheet">
                <xsl:text>type="text/xsl" href="/static/xsl/</xsl:text>
                <xsl:value-of select="$stylesheet"/>
                <xsl:text>"</xsl:text>
            </xsl:processing-instruction>
        </xsl:if>
    </xsl:template>
    
    <xsl:template match="*" />
</xsl:stylesheet>

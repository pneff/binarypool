<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:xhtml="http://www.w3.org/1999/xhtml"
    xmlns="http://www.w3.org/1999/xhtml">

    <xsl:import href="master.xsl" />
    
    <xsl:template name="page_body_content">
        <xsl:apply-templates select="/buckets" />
    </xsl:template>
    
    <xsl:template match="buckets">
        <ol class="buckets">
            <xsl:apply-templates select="bucket"/>
        </ol>        
    </xsl:template>
    
    <xsl:template match="bucket">
        <li><a href="/{@id}"><xsl:value-of select="@id"/></a></li>
    </xsl:template>

</xsl:stylesheet> 
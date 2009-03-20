<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:xhtml="http://www.w3.org/1999/xhtml"
    xmlns="http://www.w3.org/1999/xhtml">

    <xsl:import href="master.xsl" />
    
    <xsl:template name="page_head_title">upload successful ...</xsl:template>
    <xsl:template name="page_body_title">
        <a href="/{/saved/asset}">upload successful ...</a>
    </xsl:template>
    
    <xsl:template name="page_head_misc">
        <meta http-equiv="refresh" content="3;url=/{/saved/asset}"/>
    </xsl:template>
</xsl:stylesheet>
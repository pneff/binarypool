<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:xhtml="http://www.w3.org/1999/xhtml"
    xmlns="http://www.w3.org/1999/xhtml">

    <xsl:template match="/">
        <xsl:call-template name="html" />
    </xsl:template>
    
    <xsl:template name="html">
        <html lang="en" xml:lang="en">
            <xsl:call-template name="html_head" />
            <xsl:call-template name="html_body" />
        </html>
    </xsl:template>
    
    <xsl:template name="html_head">
        <head>
            <xsl:call-template name="html_head_title" />
            <xsl:call-template name="html_head_css" />
            <xsl:call-template name="html_head_js" />
            <xsl:call-template name="html_head_misc" />
        </head>
    </xsl:template>
    
    <xsl:template name="html_head_title">
        <title>
            <xsl:call-template name="html_head_title_prefix" />
            <xsl:variable name="title">
                <xsl:call-template name="page_head_title" />
            </xsl:variable>
            <xsl:if test="string-length($title) &gt; 0">
                <xsl:text> - </xsl:text>
                <xsl:copy-of select="$title" />
            </xsl:if>
        </title>
    </xsl:template>
    
    <xsl:template name="html_head_title_prefix">
        binarypool
    </xsl:template>
    
    <xsl:template name="html_head_css">
        <link rel="stylesheet" type="text/css" href="/static/css/default.css"/>
        <xsl:call-template name="page_head_css" />
    </xsl:template>
    
    <xsl:template name="html_head_js">
        <script type="text/javascript" src="/static/js/default.js"></script>
        <xsl:call-template name="page_head_js" />
    </xsl:template>
    
    <xsl:template name="html_head_misc">
        <xsl:call-template name="page_head_misc" />
    </xsl:template>
    
    <xsl:template name="html_body">
        <body>
            <xsl:call-template name="html_body_header" />
            <xsl:call-template name="html_body_content" />
        </body>
    </xsl:template>
    
    <xsl:template name="html_body_header">
        <div id="header">
            <h1>
                <xsl:call-template name="html_body_header_prefix" />
                <xsl:variable name="title">
                    <xsl:call-template name="page_body_title" />
                </xsl:variable>
                <xsl:if test="string-length($title) &gt; 0">
                    <xsl:text> - </xsl:text>
                    <xsl:copy-of select="$title" />
                </xsl:if>
            </h1>
        </div>
        <xsl:call-template name="page_body_header" />
    </xsl:template>
    
    <xsl:template name="html_body_header_prefix">
        <a href="/">binarypool</a>
    </xsl:template>
    
    <xsl:template name="html_body_content">
        <div id="content">
            <xsl:call-template name="page_body_content" />
        </div>
    </xsl:template>
    
    <xsl:template name="page_head_title" />
    <xsl:template name="page_head_css" />
    <xsl:template name="page_head_js" />
    <xsl:template name="page_head_misc" />
    <xsl:template name="page_body_title" />
    <xsl:template name="page_body_header" />
    <xsl:template name="page_body_content" />

</xsl:stylesheet> 
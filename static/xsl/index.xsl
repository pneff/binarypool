<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:xhtml="http://www.w3.org/1999/xhtml"
    xmlns="http://www.w3.org/1999/xhtml">

    <xsl:import href="master.xsl" />
    
    <xsl:template name="page_head_title">
        <xsl:value-of select="substring-before(/registry/basepath, '/')"/> - index.xml
    </xsl:template>
    <xsl:template name="page_body_title">
        <xsl:variable name="bucket">
            <xsl:value-of select="substring-before(/registry/basepath, '/')"/>
        </xsl:variable>
        <a href="/{$bucket}"><xsl:copy-of select="$bucket"/></a>
        - <a href="/{/registry/basepath}index.xml">index.xml</a>
    </xsl:template>
    
    <xsl:template name="page_body_content">
        <div>
            <fieldset>
                <legend>general info</legend>
                <ul class="info">
                    <li><strong>Asset:</strong> <xsl:value-of select="/registry/basepath"/>index.xml</li>
                    <li><strong>Created:</strong> <span class="timestamp"><xsl:value-of select="/registry/created"/></span></li>
                    <li><strong>Expires:</strong> <span class="timestamp"><xsl:value-of select="/registry/expiry"/></span></li>
                </ul>
            </fieldset>
        </div>
        <xsl:apply-templates select="/registry/items/item" />
    </xsl:template>
    
    <xsl:template match="item[@type = 'IMAGE']">
        <div>
            <fieldset>
                <xsl:choose>
                    <xsl:when test="@isRendition = 'false'">
                        <legend>original</legend>
                    </xsl:when>
                    <xsl:otherwise>
                        <legend><xsl:value-of select="./rendition"/></legend>
                    </xsl:otherwise>
                </xsl:choose>
            
                <div class="rendition">
                    <xsl:variable name="url">
                        <xsl:choose>
                            <xsl:when test="substring(./location, 1, 7) = 'http://'">
                                <xsl:value-of select="./location"/>
                            </xsl:when>
                            <xsl:otherwise>
                                <xsl:text>/</xsl:text>
                                <xsl:value-of select="./location"/>
                                </xsl:otherwise>
                        </xsl:choose>
                    </xsl:variable>
                    <img src="{$url}" width="{./imageinfo/width}" height="{./imageinfo/height}"/>
                </div>
                
                <ul class="info">
                    <li><strong>Location:</strong> <xsl:value-of select="./location"/></li>
                    <li><strong>Mime-type:</strong> <xsl:value-of select="./mimetype"/></li>
                    <li><strong>Size:</strong> <xsl:value-of select="./imageinfo/width"/>px X <xsl:value-of select="./imageinfo/height"/>px</li>
                </ul>
            </fieldset>
        </div> 
    </xsl:template>
    
    <xsl:template match="*"/>

</xsl:stylesheet>

<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:xhtml="http://www.w3.org/1999/xhtml"
    xmlns="http://www.w3.org/1999/xhtml">

    <xsl:import href="master.xsl" />
    
    <xsl:template name="page_head_title">
        <xsl:value-of select="/bucket/@id"/>
    </xsl:template>
    
    <xsl:template name="page_body_title">
        <xsl:variable name="bucket">
            <xsl:value-of select="/bucket/@id"/>
        </xsl:variable>
        <a href="/{$bucket}"><xsl:copy-of select="$bucket"/></a>
    </xsl:template>
    
    <xsl:template name="page_body_content">
        <form id="upload_form" action="/{/bucket/@id}" method="POST"
            enctype="multipart/form-data">
            <fieldset id="file">
                <legend>UPLOAD A FILE</legend>
                <input type="hidden" name="Type" value="IMAGE"/>
                <ol>
                    <li>
                    <label for="File">File : </label>
                    <input type="file" name="File"/>
                    <input type="submit" value="Upload"/>
                    </li>
                </ol>
            </fieldset>
        </form>
        
        <form id="url_form" action="/{/bucket/@id}" method="POST"
            enctype="multipart/form-data">
            <fieldset id="url">
                <legend>UPLOAD FROM A URL</legend>
                
                <input type="hidden" name="Type" value="IMAGE"/>
                <ol>
                    <li>
                        <label for="URL">URL : </label>
                        <input type="text" name="URL"/>
                        <input type="submit" value="Upload"/>
                    </li>
                </ol>
            </fieldset>
        </form>
    </xsl:template>
    
    
</xsl:stylesheet>
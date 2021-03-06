
<xsl:stylesheet version="1.0"  
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:oai="http://www.openarchives.org/OAI/2.0/" 
	xmlns:dc="http://purl.org/dc/elements/1.1/" 
	xmlns:oai_dc="http://www.openarchives.org/OAI/2.0/oai_dc/">

	<xsl:param name="sub_setname"/>
	<xsl:param name="subject"/>
	<xsl:param name="publisher"/>
	<xsl:param name="language"/>
	<xsl:param name="year"/>
	<xsl:param name="dbname"/>
	<xsl:param name="http_dir"/>
	<xsl:param name="keyword"/>

	<xsl:template match="/">
		<xsl:for-each select="oai:record/oai:metadata/oai_dc:dc/dc:subject">
			<xsl:if test="text()=$sub_setname">

			    Hello<xsl:value-of select="text()"/><br/>
			</xsl:if>
		</xsl:for-each>
		<xsl:for-each select="oai:record/oai:metadata/oai_dc:dc">
				<a href="" onClick="MM_goToURL('parent','{$http_dir}/goto.php?url={dc:identifier}&amp;dbname={$dbname}&amp;keyword={$keyword}');return document.MM_returnValue">
				<span class="tag"> <xsl:value-of select="dc:title"/></span></a><br/>
				<span class="tag">ISSN: </span> <xsl:value-of select="substring-after(dc:identifier[position()=2],'issn: ')"/><br/>
				<span class="tag">Subject: </span> 
				<xsl:for-each select="dc:subject[text() != ('DoajSubjectTerm:')=substring-after(.,'DoajSubjectTerm:')]">
					    <a href="details.php?keyword={substring-after(.,'DoajSubjectTerm:')}"><xsl:value-of select="substring-after(.,'DoajSubjectTerm: ')"/></a>  <img src="../images/global/spacer.gif" width="10"/>
				</xsl:for-each>
				<br/>
				<span class="tag">Publisher: </span> <xsl:value-of select="dc:publisher"/><br/>
				<span class="tag">Language: </span> <xsl:value-of select="dc:language"/><br/>				
				<span class="tag">Start Year: </span> <xsl:value-of select="dc:date"/><br/>			
				<small><xsl:value-of select="substring-after(dc:identifier,'http://')"/></small><br/>
				
		</xsl:for-each>
	</xsl:template>
</xsl:stylesheet>
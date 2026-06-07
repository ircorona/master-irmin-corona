<?xml version="1.0" encoding="UTF-8"?>
<!--
  sitemap.xsl — hoja de estilos ORIGINAL para los sitemaps del proyecto.
  Transforma el XML en HTML legible para humanos. Google ignora esto (0 impacto SEO).
  Autor: Irmin Corona — ejercicio Máster SEO Técnico (clase 08, sitemaps).
-->
<xsl:stylesheet version="1.0"
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
  xmlns:s="http://www.sitemaps.org/schemas/sitemap/0.9"
  xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"
  xmlns:video="http://www.google.com/schemas/sitemap-video/1.1"
  xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">

  <xsl:output method="html" encoding="UTF-8" indent="yes"/>

  <xsl:template match="/">
    <html lang="es">
      <head>
        <meta charset="UTF-8"/>
        <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
        <title>Sitemap · master-irmin-corona</title>
        <link rel="preconnect" href="https://fonts.googleapis.com"/>
        <link href="https://fonts.googleapis.com/css2?family=Catamaran:wght@400;600;800&amp;display=swap" rel="stylesheet"/>
        <link rel="stylesheet" href="/css/sitemap.css"/>
      </head>
      <body>
        <header class="sm-header">
          <h1>Sitemap</h1>
          <p class="sm-sub">
            <xsl:choose>
              <xsl:when test="s:sitemapindex">
                Índice con <xsl:value-of select="count(s:sitemapindex/s:sitemap)"/> sitemap(s)
              </xsl:when>
              <xsl:otherwise>
                <xsl:value-of select="count(s:urlset/s:url)"/> URL(s)
              </xsl:otherwise>
            </xsl:choose>
          </p>
        </header>
        <main class="sm-main">
          <xsl:apply-templates/>
        </main>
        <footer class="sm-footer">
          <p>master-irmin-corona · ejercicio Máster SEO Técnico</p>
        </footer>
      </body>
    </html>
  </xsl:template>

  <!-- ÍNDICE: tabla de sub-sitemaps -->
  <xsl:template match="s:sitemapindex">
    <table class="sm-table">
      <thead>
        <tr><th class="sm-num">#</th><th>Sitemap</th><th>Última modificación</th></tr>
      </thead>
      <tbody>
        <xsl:for-each select="s:sitemap">
          <tr>
            <td class="sm-num"><xsl:value-of select="position()"/></td>
            <td><a href="{s:loc}"><xsl:value-of select="s:loc"/></a></td>
            <td class="sm-date"><xsl:value-of select="s:lastmod"/></td>
          </tr>
        </xsl:for-each>
      </tbody>
    </table>
  </xsl:template>

  <!-- URLSET: tabla de URLs con badges de media -->
  <xsl:template match="s:urlset">
    <table class="sm-table">
      <thead>
        <tr><th class="sm-num">#</th><th>URL</th><th>Última modificación</th><th>Media</th></tr>
      </thead>
      <tbody>
        <xsl:for-each select="s:url">
          <tr>
            <td class="sm-num"><xsl:value-of select="position()"/></td>
            <td>
              <a href="{s:loc}"><xsl:value-of select="s:loc"/></a>
              <xsl:if test="news:news">
                <div class="sm-meta">📰 <xsl:value-of select="news:news/news:title"/></div>
              </xsl:if>
            </td>
            <td class="sm-date"><xsl:value-of select="s:lastmod"/></td>
            <td class="sm-badges">
              <xsl:if test="image:image">
                <span class="sm-badge sm-img"><xsl:value-of select="count(image:image)"/> img</span>
              </xsl:if>
              <xsl:if test="video:video">
                <span class="sm-badge sm-vid"><xsl:value-of select="count(video:video)"/> vid</span>
              </xsl:if>
              <xsl:if test="news:news">
                <span class="sm-badge sm-news">news</span>
              </xsl:if>
            </td>
          </tr>
        </xsl:for-each>
      </tbody>
    </table>
  </xsl:template>

</xsl:stylesheet>

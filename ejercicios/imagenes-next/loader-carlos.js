// Loader de Carlos Sánchez y Lacebot, copiado tal cual de
// https://sanchezdonate.net/articulo/optimizacion-imagenes-seo-nextjs/ (revisado 2026-09-30).
// Truco: Next llama al loader una vez por cada ancho del srcset y otra más con el último ancho
// para el atributo src. Esa segunda llamada repetida devuelve la imagen original.
let lastWidth = 0;
const imageLoaderForSeo = ({ quality = 75, src, width }) => {
  /* The loader is called for each width defined in deviceSizes (next.config.js). The last width
   * is called twice, once for the last srcset width and then for the <img> src attribute. So we
   * return in this latest case the original image for Google to use. */
  if (width === lastWidth) {
    lastWidth = 0;
    return src;
  }
  lastWidth = width;
  return `/_next/image?url=${encodeURIComponent(src)}&w=${width}&q=${quality}`;
};
export default imageLoaderForSeo;

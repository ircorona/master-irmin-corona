// JS de prueba: marca en la consola si el CSS vino de la caché del navegador.
document.addEventListener("DOMContentLoaded", () => {
  const css = performance.getEntriesByType("resource").find((r) => r.name.includes("estilo.css"));
  if (!css) return;
  // transferSize 0 con decodedBodySize > 0: servido desde la caché, sin red
  const desdeCache = css.transferSize === 0 && css.decodedBodySize > 0;
  console.log(`estilo.css ${desdeCache ? "desde la caché del navegador" : "descargado"}: ` +
    `${css.transferSize} bytes por la red, ${css.decodedBodySize} bytes descomprimido`);
});

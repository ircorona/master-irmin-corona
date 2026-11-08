// Fase 2 de prueba.py: esta página se copia a app/loader-en-servidor/page.js en un build aparte.
// Es el uso que muestra el artículo (loader como prop) pero en un componente de servidor,
// que es lo que crea Next por defecto en el App Router. Se espera que el build falle.
import Image from 'next/image';
import imageLoaderForSeo from '../../loader-carlos';

export default function LoaderEnServidor() {
  return (
    <main>
      <h1>Loader en componente de servidor</h1>
      <Image loader={imageLoaderForSeo} src="/img/gato-naranja.jpg" alt="Gato naranja" width={800} height={600} />
    </main>
  );
}

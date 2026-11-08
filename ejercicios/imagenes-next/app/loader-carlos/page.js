'use client';

// Una función como prop (loader) solo se puede pasar desde un componente de cliente,
// por eso esta página lleva 'use client'. Dos imágenes: una de ancho fijo (srcset 1x/2x)
// y otra responsive con sizes (srcset con todos los anchos), para ver si el truco aguanta
// varias imágenes seguidas.
import Image from 'next/image';
import imageLoaderForSeo from '../../loader-carlos';

export default function LoaderCarlos() {
  return (
    <main>
      <h1>Loader de Carlos</h1>
      <Image loader={imageLoaderForSeo} src="/img/gato-naranja.jpg" alt="Gato naranja" width={800} height={600} />
      <Image loader={imageLoaderForSeo} src="/img/perro-marron.jpg" alt="Perro marrón" width={1600} height={1200} sizes="100vw" />
    </main>
  );
}

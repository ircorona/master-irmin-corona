import Image from 'next/image';

// unoptimized: Next sirve el fichero tal cual, sin srcset ni formatos nuevos.
export default function SinOptimizar() {
  return (
    <main>
      <h1>unoptimized</h1>
      <Image src="/img/gato-naranja.jpg" alt="Gato naranja" width={800} height={600} unoptimized />
    </main>
  );
}

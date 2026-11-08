import Image from 'next/image';

// Componente de servidor, next/image tal cual lo trae la documentación.
export default function PorDefecto() {
  return (
    <main>
      <h1>Por defecto</h1>
      <Image src="/img/gato-naranja.jpg" alt="Gato naranja" width={800} height={600} />
    </main>
  );
}

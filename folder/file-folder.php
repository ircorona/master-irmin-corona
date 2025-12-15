<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="/ejemplo/CSS/style.css">
    <title>My Projects</title>
</head>
<body>
    <h1>My Projects</h1>
    
    <header>
        <nav>
            <ul>
                <li><a href="/">Home</a></li>
                <li><a href="/about-me.html">About Me</a></li>
                <li><a href="/contact.html">Contact</a></li>
                <li><a href="/folder/file-folder.html">Projects</a></li>
                <li><a href="https://github.com/ircorona">My GitHub</a></li>
            </ul>
        </nav>
    </header>
    
    <p>Just a few things I'm working on right now.</p>
    
    <h2>What I'm building</h2>
    <table border="1">
        <tr>
            <th>Project</th>
            <th>Tech</th>
            <th>Status</th>
            <th>Started</th>
        </tr>
        <tr>
            <td>This Website</td>
            <td>HTML5</td>
            <td>Learning</td>
            <td>October 2025</td>
        </tr>
        <tr>
            <td>WordPress Sites</td>
            <td>WordPress, Elementor</td>
            <td>Active</td>
            <td>2024</td>
        </tr>
        <tr>
            <td>Data Dashboards</td>
            <td>Power BI</td>
            <td>Daily work</td>
            <td>2024</td>
        </tr>
        <tr>
            <td>CSS Units Guide</td>
            <td>HTML5, CSS3</td>
            <td>Learning</td>
            <td>November 2025</td>
        </tr>
    </table>
    
    <h3>What's next</h3>
    <ul>
        <li>Add some CSS to make it look better</li>
        <li>Learn more JavaScript</li>
        <li>Keep practicing</li>
    </ul>

    <hr style="border: none; border-top: 3px solid #667eea; margin: 50px 0;">
    
    <!-- ========================================
         GUÍA VISUAL DE SELECTORES CSS
         ======================================== -->
    <h1 class="guia-titulo">📚 Guía Visual de Selectores CSS</h1>

    <!-- EJEMPLO 1: DOS CLASES EN EL MISMO ELEMENTO -->
    <div class="ejemplo">
        <div class="titulo-selector">1️⃣ Selector: .class1.class2 (SIN ESPACIO)</div>

        <div class="codigo">
            <span class="selector-clase">.caja.especial</span> <span class="selector-syntax">{ background: pink; }</span>
        </div>

        <div class="nota">
            <strong>📌 Regla:</strong> El elemento debe tener AMBAS clases al mismo tiempo.<br>
            <strong>Sin espacio</strong> entre las clases = mismo elemento.
        </div>

        <div class="visual-demo">
            <div class="html-structure">
                <h3>🔍 HTML:</h3>
                <div class="estructura-arbol">
                    <div class="nivel-1">&lt;div class="caja"&gt; <span class="no">❌</span></div>
                    <div class="nivel-1">&lt;div class="especial"&gt; <span class="no">❌</span></div>
                    <div class="nivel-1">&lt;div class="caja especial"&gt; <span class="si">✅</span></div>
                </div>
            </div>

            <div class="flecha">→</div>

            <div class="resultado">
                <h3>✨ Resultado:</h3>
                <div class="caja">Solo "caja"</div>
                <div class="especial">Solo "especial"</div>
                <div class="caja especial">¡AMBAS clases!</div>
            </div>
        </div>
    </div>

    <!-- EJEMPLO 2: CLASE DENTRO DE OTRA -->
    <div class="ejemplo">
        <div class="titulo-selector">2️⃣ Selector: .parent .child (CON ESPACIO)</div>

        <div class="codigo">
            <span class="selector-clase">.contenedor-demo .destacado-demo</span> <span class="selector-syntax">{ background: blue; }</span>
        </div>

        <div class="nota">
            <strong>📌 Regla:</strong> El elemento .destacado-demo debe estar DENTRO de .contenedor-demo.<br>
            <strong>Con espacio</strong> = relación padre-hijo (descendiente).
        </div>

        <div class="visual-demo">
            <div class="html-structure">
                <h3>🔍 HTML:</h3>
                <div class="estructura-arbol">
                    <div class="nivel-1">&lt;div class="contenedor-demo"&gt;</div>
                    <div class="nivel-2">&lt;div class="destacado-demo"&gt; <span class="si">✅</span></div>
                    <div class="nivel-1">&lt;/div&gt;</div>
                    <br>
                    <div class="nivel-1">&lt;div class="destacado-demo"&gt; <span class="no">❌</span></div>
                </div>
            </div>

            <div class="flecha">→</div>

            <div class="resultado">
                <h3>✨ Resultado:</h3>
                <div class="contenedor-demo">
                    <strong>Contenedor</strong>
                    <div class="destacado-demo">¡Dentro del contenedor! ✅</div>
                </div>
                <div class="destacado-demo">Fuera del contenedor ❌</div>
            </div>
        </div>
    </div>

    <!-- EJEMPLO 3: SELECTOR DE GRUPO -->
    <div class="ejemplo">
        <div class="titulo-selector">3️⃣ Selector: .class1, .class2 (CON COMA)</div>

        <div class="codigo">
            <span class="selector-clase">.rojo-demo</span><span class="selector-syntax">,</span> <span class="selector-clase">.azul-demo</span> <span class="selector-syntax">{ background: purple; }</span>
        </div>

        <div class="nota">
            <strong>📌 Regla:</strong> Aplica el MISMO estilo a ambas clases por separado.<br>
            <strong>Con coma</strong> = selector de grupo (clases independientes).
        </div>

        <div class="visual-demo">
            <div class="html-structure">
                <h3>🔍 HTML:</h3>
                <div class="estructura-arbol">
                    <div class="nivel-1">&lt;div class="rojo-demo"&gt; <span class="si">✅</span></div>
                    <div class="nivel-1">&lt;div class="azul-demo"&gt; <span class="si">✅</span></div>
                </div>
            </div>

            <div class="flecha">→</div>

            <div class="resultado">
                <h3>✨ Resultado:</h3>
                <div class="rojo-demo">Clase "rojo-demo" ✅</div>
                <div class="azul-demo">Clase "azul-demo" ✅</div>
            </div>
        </div>
    </div>

    <!-- RESUMEN FINAL -->
    <div class="ejemplo" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
        <h2 style="text-align: center; font-size: 32px;">📋 Resumen Rápido</h2>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 20px;">
            <div style="background: rgba(255,255,255,0.1); padding: 20px; border-radius: 10px;">
                <h3>🔗 SIN ESPACIO</h3>
                <div class="codigo" style="background: rgba(0,0,0,0.3);">.clase1.clase2</div>
                <p>Mismo elemento con AMBAS clases</p>
            </div>

            <div style="background: rgba(255,255,255,0.1); padding: 20px; border-radius: 10px;">
                <h3>👨‍👦 CON ESPACIO</h3>
                <div class="codigo" style="background: rgba(0,0,0,0.3);">.padre .hijo</div>
                <p>.hijo DENTRO de .padre</p>
            </div>

            <div style="background: rgba(255,255,255,0.1); padding: 20px; border-radius: 10px;">
                <h3>📝 CON COMA</h3>
                <div class="codigo" style="background: rgba(0,0,0,0.3);">.clase1, .clase2</div>
                <p>Ambas reciben el MISMO estilo</p>
            </div>
        </div>
    </div>

    <hr style="border: none; border-top: 3px solid #667eea; margin: 50px 0;">

    <!-- ========================================
         EJEMPLO 4: Z-INDEX CON EXODIA
         ======================================== -->
    <div class="ejemplo">
        <div class="titulo-selector">4️⃣ Z-Index: Controlando las Capas</div>

        <div class="nota">
            <strong>📌 Concepto:</strong> El z-index controla qué elementos aparecen "encima" de otros.<br>
            <strong>Números más altos</strong> = más cerca de ti.
        </div>

        <div style="margin-top: 30px;">
            <h3>✨ Capas Visualizadas:</h3>
            <div style="position: relative; height: 200px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 10px; padding: 20px;">
                
                <div style="position: absolute; bottom: 30px; left: 40px; width: 200px; height: 80px; background: #e0e0e0; border: 3px solid #999; border-radius: 8px; padding: 15px; z-index: 1;">
                    <strong>z-index: 1</strong><br>
                    Contenido Normal
                </div>

                <div style="position: absolute; bottom: 50px; left: 100px; width: 200px; height: 100px; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; border: 4px solid #ffd700; border-radius: 8px; padding: 15px; z-index: 500;">
                    <strong>z-index: 500</strong><br>
                    🎴 Exodia
                </div>

                <div style="position: absolute; top: 20px; right: 40px; width: 200px; height: 60px; background: #0066cc; color: white; border: 3px solid #004499; border-radius: 8px; padding: 15px; z-index: 1000;">
                    <strong>z-index: 1000</strong><br>
                    Header
                </div>
            </div>
        </div>

        <div style="margin-top: 20px; padding: 15px; background: #e3f2fd; border-left: 5px solid #2196f3; border-radius: 5px;">
            <strong>🎯 Pruébalo:</strong> Mira la esquina inferior derecha. ¡Ahí está Exodia flotando! 
            Haz scroll hacia arriba y verás cómo pasa DEBAJO del header.
        </div>
    </div>

    <hr style="border: none; border-top: 3px solid #667eea; margin: 50px 0;">

    <!-- MEDIDAS RELATIVAS -->
    <section class="medidas opacity60 funciona">
        <h2>Medidas Relativas (Relative Units)</h2>
        
        <div class="relative-sizes">
            <div class="em">
                <p><strong>em</strong> - Multiplies parent font size (2em = 2x parent)</p>
            </div>

            <div class="rem">
                <p><strong>rem</strong> - Multiplies root font size (2rem = 2x html)</p>
            </div>

            <div class="vh">
                <p><strong>vh</strong> - Viewport Height (20vh = 20% of screen height)</p>
            </div>

            <div class="vw">
                <p><strong>vw</strong> - Viewport Width (50vw = 50% of screen width)</p>
            </div>

            <div class="percent">
                <p><strong>%</strong> - Percentage (75% = 75% of parent size)</p>
            </div>
        </div>
    </section>

    <!-- EJEMPLOS DE SELECTORES EN ACCIÓN -->
    <div class="intro highlight opacity60">
        Ejemplo: TWO classes on same element (.intro.highlight)
    </div>
    
    <p class="intro opacity60 cabeza">
        Ejemplo: Paragraph with class (.intro on &lt;p&gt;)
    </p>

    <div class="container">
        <h3>🎯 Selector Descendiente</h3>
        <div class="highlight">
            ⭐ Dentro de .container ⭐
        </div>
    </div>
    
    <!-- DISPLAY EXAMPLES -->
    <h2>Display Property</h2>
    <div class="display-block">BLOCK - Full width, new line</div>
    <div class="display-block">Another BLOCK</div>

    <div>
        <span class="display-inline">Inline 1</span>
        <span class="display-inline">Inline 2</span>
        <span class="display-inline">Inline 3</span>
    </div>

    <p>Check out my <a href="https://github.com/ircorona">GitHub</a> or <a href="https://climbthesearches.com/">website</a></p>
    
    <!-- EXODIA FLOTANTE -->
    <img src="/images/Exodia.webp" alt="Exodia" class="exodia-flotante">

    <footer>
        <p><a href="/">Back to home</a></p>
        <p>&copy; 2025 Irmin Corona</p>
        <p>Mexico City, Mexico</p>
    </footer>
</body>
</html>
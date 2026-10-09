// app.js — versión Semana 10: la de la Semana 9, con la URL de la API relativa
// y manejo de errores (respuesta.ok) en las cuatro operaciones.

// Dirección relativa: funciona en XAMPP y en la nube, sin tocar nada
const URL_API = "../api/alumnos.php";

const mensaje = document.querySelector("#mensaje");

// ----- El mapa: Leaflet dibuja, OpenStreetMap pone las imágenes -----
const mapa = L.map("mapa").setView([-38.4, -63.6], 4);   // centrado en Argentina
L.tileLayer("https://tile.openstreetmap.org/{z}/{x}/{y}.png", {
  maxZoom: 19,
  attribution: "&copy; colaboradores de OpenStreetMap",
}).addTo(mapa);

const capaMarcadores = L.featureGroup().addTo(mapa);
let marcadores = {};     // id del alumno -> su marcador, para poder quitarlo al borrar

function agregarMarcador(alumno) {
  if (alumno.latitud === null) return;   // sin coordenadas no hay nada que dibujar
  const marcador = L.marker([Number(alumno.latitud), Number(alumno.longitud)])
    .bindPopup(`${alumno.nombre} — ${alumno.ciudad}`.replace(/</g, "&lt;"))
    .addTo(capaMarcadores);
  marcadores[alumno.id] = marcador;
}

// Lee el mensaje de error de una respuesta que no fue ok y lo muestra.
async function mostrarError(respuesta) {
  let texto = "Error inesperado";
  try {
    texto = (await respuesta.json()).error ?? texto;
  } catch {
    // la respuesta no era JSON: queda el texto genérico
  }
  mensaje.textContent = `Error ${respuesta.status}: ${texto}`;
}

// ----- Listar -----
async function listarAlumnos() {
  try {
    const respuesta = await fetch(URL_API);
    if (!respuesta.ok) {
      await mostrarError(respuesta);
      return;
    }
    renderizarAlumnos(await respuesta.json());
  } catch {
    mensaje.textContent = "No se pudo conectar con la API";
  }
}

// Una celda con texto (textContent: el navegador no interpreta HTML que venga en los datos)
function celda(texto) {
  const td = document.createElement("td");
  td.textContent = texto;
  return td;
}

function renderizarAlumnos(alumnos) {
  const cuerpo = document.querySelector("#cuerpoTabla");
  cuerpo.innerHTML = "";
  capaMarcadores.clearLayers();   // igual que la tabla: se limpia antes de redibujar
  marcadores = {};

  alumnos.forEach(function (alumno) {
    const fila = document.createElement("tr");
    fila.dataset.id = alumno.id;
    fila.append(celda(alumno.id), celda(alumno.nombre), celda(alumno.email), celda(alumno.ciudad ?? "—"));

    const acciones = document.createElement("td");
    acciones.innerHTML = `<button class="btn-editar">Editar</button>
                          <button class="btn-borrar">Borrar</button>`;
    fila.appendChild(acciones);

    cuerpo.appendChild(fila);
    agregarMarcador(alumno);
  });

  // encuadrar el mapa para que se vean todos los marcadores
  if (capaMarcadores.getLayers().length > 0) {
    mapa.fitBounds(capaMarcadores.getBounds(), { padding: [60, 60], maxZoom: 6 });
  }
}

// ----- Alta -----
document.querySelector("#formAlta").addEventListener("submit", async function (evento) {
  evento.preventDefault();
  mensaje.textContent = "Guardando…";

  const nuevoAlumno = {
    nombre: document.querySelector("#campoNombre").value,
    email: document.querySelector("#campoEmail").value,
    ciudad: document.querySelector("#campoCiudad").value,
  };

  const respuesta = await fetch(URL_API, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(nuevoAlumno),
  });

  if (!respuesta.ok) {
    await mostrarError(respuesta);
    return;
  }

  mensaje.textContent = "";
  document.querySelector("#formAlta").reset();
  listarAlumnos();
});

// ----- Baja y edición -----
document.querySelector("#cuerpoTabla").addEventListener("click", async function (evento) {
  const fila = evento.target.closest("tr");
  if (!fila) return;
  const id = fila.dataset.id;

  if (evento.target.classList.contains("btn-borrar")) {
    const respuesta = await fetch(`${URL_API}?id=${id}`, { method: "DELETE" });
    if (!respuesta.ok) {
      await mostrarError(respuesta);
      return;
    }
    mensaje.textContent = "";
    fila.remove();
    if (marcadores[id]) {
      capaMarcadores.removeLayer(marcadores[id]);
      delete marcadores[id];
    }
  }

  if (evento.target.classList.contains("btn-editar")) {
    const nombreNuevo = prompt("Nuevo nombre:", fila.children[1].textContent);
    if (nombreNuevo === null) return;
    const emailNuevo = prompt("Nuevo email:", fila.children[2].textContent);
    if (emailNuevo === null) return;

    const respuesta = await fetch(`${URL_API}?id=${id}`, {
      method: "PUT",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ nombre: nombreNuevo, email: emailNuevo }),
    });
    if (!respuesta.ok) {
      await mostrarError(respuesta);
      return;
    }
    const alumnoActualizado = await respuesta.json();
    mensaje.textContent = "";
    fila.children[1].textContent = alumnoActualizado.nombre;
    fila.children[2].textContent = alumnoActualizado.email;
  }
});

listarAlumnos();

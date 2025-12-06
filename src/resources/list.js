/*

  Requirement: Populate the "Course Resources" list page.

*/

// --- Element Selections ---

const listSection = document.querySelector('#resource-list-section');

// --- Functions ---

// ينشئ <article> واحد لمورد واحد

function createResourceArticle(resource) {

  const { id, title, description } = resource;

  const article = document.createElement('article');

  const h2 = document.createElement('h2');

  h2.textContent = title;

  const p = document.createElement('p');

  p.textContent = description;

  const link = document.createElement('a');

  link.textContent = 'View Resource & Discussion';

  link.href = `details.html?id=${id}`;

  article.appendChild(h2);

  article.appendChild(p);

  article.appendChild(link);

  return article;

}

// يجلب الموارد من JSON ويعرضها في الصفحة

async function loadResources() {

  try {

    const response = await fetch('api/resources.json'); // نفس مكان الملف

    const resources = await response.json();

    // نفرغ المحتوى القديم

    listSection.innerHTML = '';

    // نضيف كل الموارد

    resources.forEach(resource => {

      const article = createResourceArticle(resource);

      listSection.appendChild(article);

      // خط فاصل مثل اللي في HTML الأصلي

      const hr = document.createElement('hr');

      listSection.appendChild(hr);

    });

  } catch (error) {

    console.error('Error loading resources:', error);

    listSection.innerHTML = '<p>Failed to load resources.</p>';

  }

}

// --- Initial Page Load ---

loadResources();
 
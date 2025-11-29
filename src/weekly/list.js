  /*
    Requirement: Populate the "Weekly Course Breakdown" list page.

    Instructions:
    1. Link this file to `list.html` using:
      <script src="list.js" defer></script>

    2. In `list.html`, add an `id="week-list-section"` to the
      <section> element that will contain the weekly articles.

    3. Implement the TODOs below.
  */

  //const { createElement } = require("react");

  // --- Element Selections ---
  // TODO: Select the section for the week list ('#week-list-section').
  const listSection= document.getElementById("week-list-section");


  // --- Functions ---

  /**
   * TODO: Implement the createWeekArticle function.
   * It takes one week object {id, title, startDate, description}.
   * It should return an <article> element matching the structure in `list.html`.
   * - The "View Details & Discussion" link's `href` MUST be set to `details.html?id=${id}`.
   * (This is how the detail page will know which week to load).
   */
  function createWeekArticle(week) {
    // ... your implementation here ...
    const article= document.createElement("article");

    const title= document.createElement("h2");
    title.textContent= week.title;
    article.appendChild(title);

    const startDate= document.createElement("p");
    startDate.textContent= `Starts on: ${week.startDate}`;
    article.appendChild(startDate);

    const description= document.createElement("p");
    description.textContent= week.description;
    article.appendChild(description);

    const links= document.createElement("a");
    links.href= `details.html?id=${week.id}`;
    links.textContent="View Details & Discussion";
    links.setAttribute('role', 'button');
    article.appendChild(links);

    return article;
  }

  


  /**
   * TODO: Implement the loadWeeks function.
   * This function needs to be 'async'.
   * It should:
   * 1. Use `fetch()` to get data from 'weeks.json'.
   * 2. Parse the JSON response into an array.
   * 3. Clear any existing content from `listSection`.
   * 4. Loop through the weeks array. For each week:
   * - Call `createWeekArticle()`.
   * - Append the returned <article> element to `listSection`.
   */

  async function loadWeeks() {
    // ... your implementation here ...
    
    try{
       // First, try to get weeks from localStorage
      let listWeek = JSON.parse(localStorage.getItem("weeksData"));
      //const response= await fetch("api/weeks.json");
      
      if(!listWeek){
        // If nothing in localStorage, fetch from JSON file
        const response= await fetch("api/weeks.json");
        listWeek= await response.json();
        console.log("Loaded weeks:", listWeek);

        // Save initial JSON data to localStorage so future updates persist
        localStorage.setItem("weeksData", JSON.stringify(listWeek));
      }
      else {
      console.log("Loaded weeks from localStorage:", listWeek);
    }
  

      listSection.innerHTML="";
      console.log("Cleared existing content in listSection.");


      listWeek.forEach(element => { 
        const weekArticle=createWeekArticle(element);
        listSection.appendChild(weekArticle);
      });
      console.log("All week articles appended to listSection.");

    } 
    catch(error){
      console.log('Error:', error);
    }
  }

  // --- Initial Page Load ---
  // Call the function to populate the page.
  loadWeeks();

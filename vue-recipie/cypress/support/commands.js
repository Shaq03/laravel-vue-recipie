// ***********************************************
// This example commands.js shows you how to
// create various custom commands and overwrite
// existing commands.
// ***********************************************
//
// Cypress.Commands.add('login', (email, password) => { ... })
//
// Cypress.Commands.add('drag', { prevSubject: 'element'}, (subject, options) => { ... })
//
// Cypress.Commands.add('dismiss', { prevSubject: 'optional'}, (subject, options) => { ... })
//
// Cypress.Commands.overwrite('visit', (originalFn, url, options) => { ... })

// ***********************************************
// Custom commands for recipe application testing
// ***********************************************

// Recipe Search Commands
Cypress.Commands.add('searchRecipe', (searchTerm) => {
  cy.get('[data-test="search-input"]').clear().type(searchTerm)
  cy.get('[data-test="search-button"]').click()
})

Cypress.Commands.add('clearSearch', () => {
  cy.get('[data-test="search-input"]').clear()
})

// Recipe Navigation Commands
Cypress.Commands.add('navigateToRecipe', (recipeId) => {
  cy.get(`[data-test="recipe-${recipeId}"]`).click()
})

Cypress.Commands.add('navigateToHome', () => {
  cy.get('[data-test="home-link"]').click()
})

// Recipe Interaction Commands
Cypress.Commands.add('addToFavorites', (recipeId) => {
  cy.get(`[data-test="favorite-button-${recipeId}"]`).click()
})

Cypress.Commands.add('removeFromFavorites', (recipeId) => {
  cy.get(`[data-test="favorite-button-${recipeId}"]`).click()
})

Cypress.Commands.add('rateRecipe', (recipeId, rating) => {
  cy.get(`[data-test="rating-${recipeId}"]`).click()
  cy.get(`[data-test="rating-${rating}"]`).click()
})

// Recipe Form Commands
Cypress.Commands.add('fillRecipeForm', (recipeData) => {
  cy.get('[data-test="recipe-title"]').type(recipeData.title)
  cy.get('[data-test="recipe-description"]').type(recipeData.description)
  cy.get('[data-test="recipe-ingredients"]').type(recipeData.ingredients)
  cy.get('[data-test="recipe-instructions"]').type(recipeData.instructions)
  cy.get('[data-test="recipe-submit"]').click()
})

// Recipe List Commands
Cypress.Commands.add('verifyRecipeInList', (recipeTitle) => {
  cy.get('[data-test="recipe-list"]').should('contain', recipeTitle)
})

Cypress.Commands.add('verifyRecipeNotInList', (recipeTitle) => {
  cy.get('[data-test="recipe-list"]').should('not.contain', recipeTitle)
})

// Filter Commands
Cypress.Commands.add('filterByCategory', (category) => {
  cy.get('[data-test="category-filter"]').select(category)
})

Cypress.Commands.add('filterByDifficulty', (difficulty) => {
  cy.get('[data-test="difficulty-filter"]').select(difficulty)
})

// Authentication Commands
Cypress.Commands.add('register', (userData = {}) => {
  const defaultUser = {
    name: 'Test User',
    email: `test${Date.now()}@example.com`,
    password: 'password123'
  }
  const user = { ...defaultUser, ...userData }

  cy.visit('/register')
  cy.get('[data-test="name-input"]').type(user.name)
  cy.get('[data-test="email-input"]').type(user.email)
  cy.get('[data-test="password-input"]').type(user.password)
  cy.get('[data-test="password-confirm-input"]').type(user.password)
  cy.get('[data-test="submit-register"]').click()
})

Cypress.Commands.add('login', (userData = {}) => {
  const defaultUser = {
    email: 'test@example.com',
    password: 'password123'
  }
  const user = { ...defaultUser, ...userData }

  cy.visit('/login')
  cy.get('[data-test="email-input"]').type(user.email)
  cy.get('[data-test="password-input"]').type(user.password)
  cy.get('[data-test="submit-login"]').click()
})

Cypress.Commands.add('logout', () => {
  cy.get('[data-test="user-menu"]').click()
  cy.get('[data-test="logout-button"]').click()
})

// Recipe Commands
Cypress.Commands.add('createRecipe', (recipeData = {}) => {
  const defaultRecipe = {
    title: 'Test Recipe',
    description: 'A delicious test recipe',
    ingredients: 'Ingredient 1, Ingredient 2',
    instructions: 'Step 1, Step 2',
    cuisine: 'Italian'
  }
  const recipe = { ...defaultRecipe, ...recipeData }

  cy.visit('/recipes/create')
  cy.get('[data-test="recipe-title"]').type(recipe.title)
  cy.get('[data-test="recipe-description"]').type(recipe.description)
  cy.get('[data-test="recipe-ingredients"]').type(recipe.ingredients)
  cy.get('[data-test="recipe-instructions"]').type(recipe.instructions)
  cy.get('[data-test="recipe-cuisine"]').select(recipe.cuisine)
  cy.get('[data-test="recipe-submit"]').click()
})

Cypress.Commands.add('filterByCuisine', (cuisine) => {
  cy.get('[data-test="cuisine-filter"]').select(cuisine)
})

// Utility Commands
Cypress.Commands.add('waitForApi', (method, url) => {
  cy.intercept(method, url).as('apiCall')
  cy.wait('@apiCall')
})

Cypress.Commands.add('verifyToastMessage', (message) => {
  cy.get('[data-test="toast-message"]').should('contain', message)
}) 
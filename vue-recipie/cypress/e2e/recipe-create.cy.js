describe('Recipe Creation', () => {
  beforeEach(() => {
    // Mock authentication
    cy.window().then((win) => {
      win.localStorage.setItem('token', 'fake-token')
    })
    cy.visit('/recipes/create')
  })

  it('should load the recipe creation page', () => {
    cy.get('h1').should('contain', 'Create New Recipe')
  })

  it('should create a new recipe with all required fields', () => {
    // Fill in basic info
    cy.get('#title').type('Test Recipe')
    cy.get('#description').type('A delicious test recipe')
    cy.get('#cooking_time').type('30 minutes')
    cy.get('#servings').type('4')
    cy.get('#difficulty').select('medium')

    // Add ingredients
    cy.get('input[placeholder^="Ingredient"]').first().type('2 cups flour')
    cy.get('button').contains('Add Ingredient').click()
    cy.get('input[placeholder^="Ingredient"]').last().type('1 cup sugar')

    // Add instructions
    cy.get('input[placeholder^="Step"]').first().type('Mix dry ingredients')
    cy.get('button').contains('Add Step').click()
    cy.get('input[placeholder^="Step"]').last().type('Bake at 350°F')

    // Add optional image URL
    cy.get('#image_url').type('https://example.com/test-recipe.jpg')

    // Submit the form
    cy.get('form').submit()

    // Should redirect to recipes page after successful creation
    cy.url().should('include', '/recipes')
  })

  it('should show validation errors for required fields', () => {
    // Try to submit without filling required fields
    cy.get('form').submit()

    // Check for validation messages
    cy.get('#title').should('have.attr', 'required')
    cy.get('#description').should('have.attr', 'required')
    cy.get('#cooking_time').should('have.attr', 'required')
    cy.get('#servings').should('have.attr', 'required')
    cy.get('#difficulty').should('have.attr', 'required')
  })
}) 
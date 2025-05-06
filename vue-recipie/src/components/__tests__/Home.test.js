import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import Home from '../Home.vue'

describe('Home.vue', () => {
  it('renders the hero section', () => {
    const wrapper = mount(Home)
    expect(wrapper.find('h1').text()).toBe('Discover & Share Amazing Recipes')
  })

  it('renders the features section', () => {
    const wrapper = mount(Home)
    const features = wrapper.findAll('h3')
    expect(features).toHaveLength(3)
  })

  it('renders the CTA section', () => {
    const wrapper = mount(Home)
    expect(wrapper.text()).toContain('Ready to start cooking?')
  })
}) 
import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import Home from '../Home.vue'

describe('Home.vue', () => {
  it('renders the hero section', () => {
    const wrapper = mount(Home)
    expect(wrapper.find('h1').exists()).toBe(true)
    expect(wrapper.find('p').exists()).toBe(true)
  })

  it('renders the features section', () => {
    const wrapper = mount(Home)
    const features = wrapper.findAll('dt')
    expect(features).toHaveLength(3)
  })

  it('renders the CTA section', () => {
    const wrapper = mount(Home)
    expect(wrapper.find('.bg-indigo-600').exists()).toBe(true)
  })
}) 
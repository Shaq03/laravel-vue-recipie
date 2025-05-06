import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import CookingHistory from '../CookingHistory.vue';
import { createStore } from 'vuex';
import axios from '../../axios';

vi.mock('../../axios');

const mockHistory = [
  {
    id: 1,
    recipe_id: 1,
    rating: 4,
    notes: 'Tasty!',
    cooked_at: '2024-06-01T12:00:00Z',
    recipe: {
      id: 1,
      title: 'Test Recipe',
      image_url: '',
      servings: 2,
      cooking_time: 30,
      description: 'A test recipe',
      instructions: ['Step 1', 'Step 2'],
      ingredients: ['Egg', 'Flour'],
      difficulty: 'easy',
      cuisines: ['Italian'],
      tags: ['tag1'],
      dietary_restrictions: ['vegetarian']
    }
  }
];

describe('CookingHistory.vue', () => {
  let store;

  beforeEach(() => {
    store = createStore({
      state: {},
      getters: {},
      actions: {}
    });
    vi.clearAllMocks();
  });

  it('renders loading state', async () => {
    axios.get.mockImplementation(() => new Promise(() => {}));
    const wrapper = mount(CookingHistory, {
      global: { plugins: [store] }
    });
    expect(wrapper.html()).toMatch(/loading|spin/i);
  });

  it('renders empty state', async () => {
    axios.get.mockResolvedValue({ data: [] });
    const wrapper = mount(CookingHistory, {
      global: { plugins: [store] }
    });
    await flushPromises();
    expect(wrapper.text()).toContain('No cooking history yet');
  });

  it('renders cooking history list', async () => {
    axios.get.mockResolvedValue({ data: mockHistory });
    const wrapper = mount(CookingHistory, {
      global: { plugins: [store] }
    });
    await flushPromises();
    expect(wrapper.text()).toContain('Test Recipe');
    expect(wrapper.text()).toContain('Tasty!');
    expect(wrapper.text()).toContain('4/5');
  });

  it('handles error state', async () => {
    axios.get.mockRejectedValue({ response: { data: { message: 'Failed to fetch' } } });
    const wrapper = mount(CookingHistory, {
      global: { plugins: [store] }
    });
    await flushPromises();
    expect(wrapper.text()).toContain('Failed to fetch');
  });

  it('can start editing an entry', async () => {
    axios.get.mockResolvedValue({ data: mockHistory });
    const wrapper = mount(CookingHistory, {
      global: { plugins: [store] }
    });
    await flushPromises();
    const editBtn = wrapper.findAll('button').find(btn => btn.text().toLowerCase().includes('edit'));
    expect(editBtn).toBeTruthy();
    await editBtn.trigger('click');
    expect(wrapper.html()).toContain('Save Changes');
  });

  it('can delete an entry', async () => {
    axios.get.mockResolvedValue({ data: mockHistory });
    axios.delete.mockResolvedValue({});
    window.confirm = vi.fn(() => true);
    const wrapper = mount(CookingHistory, {
      global: { plugins: [store] }
    });
    await flushPromises();
    const deleteBtn = wrapper.findAll('button').find(btn => btn.text().toLowerCase().includes('delete'));
    expect(deleteBtn).toBeTruthy();
    await deleteBtn.trigger('click');
    expect(axios.delete).toHaveBeenCalled();
  });
}); 
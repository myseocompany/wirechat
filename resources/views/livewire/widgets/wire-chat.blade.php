<div>

    @script
        <script>
            window.ChatWidget = () => {
                return {
                    show: false,
                    showActiveComponent: true,
                    activeWidgetComponent: false,
                    componentHistory: [],
                    listeners: [],
                    //current component attributes
                    closeOnEscape: false,
                    closeOnEscapeIsForceful: false,
                    dispatchCloseEvent: false,
                    destroyOnClose: false,
                    closeModalOnClickAway:false,
                    closeChatWidgetOnEscape(trigger) {

                        ///Only proceed if the trigger is for ChatDrawer
                        if (trigger.modalType !== 'ChatWidget') {
                            return;
                        }

                        //check if canCloseOnEsp
                        if (this.closeOnEscape === false) {
                            return;
                        }

                        //Fire closingModalOnEscape:event to parent
                        if (!this.closingModal('closingModalOnEscape')) {
                            return;
                        }

                        //check if should also close all children modal when this current on is closed
                        const force = this.closeOnEscapeIsForceful === true;
                        this.closeWidget(force);
                    },
                    closingModal(eventName) {
                        const componentName = this.$wire.get('widgetComponents')[this.activeWidgetComponent].name;

                        var params = {
                            id: this.activeWidgetComponent,
                            closing: true,
                        };

                        Livewire.dispatchTo(componentName, eventName, params);

                        return params.closing;
                    },

                    closeWidget(force = false, skipPreviousModals = 0, destroySkipped = false) {

                        if (this.show === false) {
                            return;
                        }

                        //Check if should dispatch events
                        if (this.dispatchCloseEvent === true) {
                            const componentName = this.$wire.get('widgetComponents')[this.activeWidgetComponent].name;
                            Livewire.dispatch('chatWidgetClosed', {
                                name: componentName
                            });
                        }

                        //Check if should completley destroy component on close 
                        //Meaning state won't be retained if component is opened again
                        if (this.destroyOnClose === true) {

                            Livewire.dispatch('destroyChatWidget', {
                                id: this.activeWidgetComponent
                            });
                        }

                        const id = this.componentHistory.pop();
                        if (id && !force) {
                            if (id) {
                                this.setActiveWidgetComponent(id, true);
                            } else {
                                this.setShowPropertyTo(false);
                            }
                        } else {
                            this.setShowPropertyTo(false);
                        }

                    },

                    setActiveWidgetComponent(id, skip = false) {

                        this.setShowPropertyTo(true);
                      //  this.closeWidget(true);

                        
                        if (this.activeWidgetComponent === id) {
                            return;
                        }

                        if (this.activeWidgetComponent !== false && skip === false) {
                            this.componentHistory.push(this.activeWidgetComponent);
                        }

                        let focusableTimeout = 50;

                        if (this.activeWidgetComponent === false) {
                            this.activeWidgetComponent = id
                            this.showActiveComponent = true;
                        } else {

                            this.showActiveComponent = false;
                            focusableTimeout = 400;

                            setTimeout(() => {
                                this.activeWidgetComponent = id;
                                this.showActiveComponent = true;
                            }, 300);
                        }
                        
                        
                        // Fetch modal attributes and set Alpine properties 
                        const attributes = this.$wire.get('widgetComponents')[id]?.modalAttributes || {};
                        this.closeOnEscape = attributes.closeOnEscape ?? false;
                        this.closeOnEscapeIsForceful = attributes.closeOnEscapeIsForceful ?? false;
                        this.dispatchCloseEvent = attributes.dispatchCloseEvent ?? false;
                        this.destroyOnClose = attributes.destroyOnClose ?? false; 
                        this.closeModalOnClickAway = attributes.closeModalOnClickAway ?? false; 

                        this.$nextTick(() => {
                            let focusable = this.$refs[id]?.querySelector('[autofocus]');
                            if (focusable) {
                                setTimeout(() => {
                                    focusable.focus();
                                }, focusableTimeout);
                            }
                        });
                    },

                    setShowPropertyTo(show) {
                        this.show = show;
                        if (show) {
                            document.body.classList.add('overflow-y-hidden');
                        } else {
                            document.body.classList.remove('overflow-y-hidden');

                            setTimeout(() => {
                                this.activeWidgetComponent = false;
                                this.$wire.resetState();
                            }, 300);
                        }
                    },
                    init() {

    
                        /*! Changed the event to closeChatWidget in order to not interfere with the main modal */
                        this.listeners.push(Livewire.on('closeChatWidget', (data) => { this.closeWidget(data?.force ?? false, data?.skipPreviousModals ?? 0, data ?.destroySkipped ?? false); }));

                        /*! Changed listener name to activeChatWidgetComponentChanged to not interfer with main modal*/
                        this.listeners.push(Livewire.on('activeChatWidgetComponentChanged', ({id}) => {
                            this.setActiveWidgetComponent(id);
                        }));
                    },
                    destroy() {
                        this.listeners.forEach((listener) => {
                            listener();
                        });
                    }
                };
            }
        </script>
    @endscript


    <div 
    x-data="{
        selectedConversationId:null,
    }"
     class ='w-full h-[calc(100vh_-_10.0rem)] bg-white dark:bg-gray-900 border dark:border-gray-700 flex overflow-hidden rounded-lg'>
      <div class="relative  w-full h-full   md:w-[360px] lg:w-[400px] xl:w-[450px] shrink-0 overflow-y-auto  ">
          <livewire:chats :widget="true" />
      </div>
      <main
           x-data="ChatWidget()" 
           x-on:open-chat="this.selectedConversationId= $event.detail.conversation;"
           x-on:close-chat.stop="setShowPropertyTo(false)"
           x-on:keydown.escape.stop.window="closeChatWidgetOnEscape({ modalType: 'ChatWidget', event: $event });"
           aria-modal="true"
           tabindex="0"
           class=" hidden md:grid grid  w-full  grow"
           style="contain:content;">
            <div 
                x-cloak
                x-show="show && showActiveComponent" x-transition:enter="ease-out duration-100"
                x-transition:enter-start="opacity-0 -translate-x-full" x-transition:enter-end="opacity-100 translate-x-0"
                x-transition:leave="ease-in duration-100 " x-transition:leave-start="opacity-100 translate-x-0"
                x-transition:leave-end="opacity-0 -translate-x-full"
                class="fixed inset-0" id="chatwidget-container"
                x-trap.noscroll.inert="show && showActiveComponent" aria-modal="true">
                @forelse($widgetComponents as $id => $component)
                    <div  x-show.immediate="activeWidgetComponent == '{{ $id }}'" x-ref="{{ $id }}"
                         wire:key="key-{{$id }}" class="h-full">
                        @livewire($component['name'], ['conversation'=> $component['conversationId'] ,'widget'=>true], key($id))
                    </div>
                @empty
                @endforelse
            </div>

            <div  x-show="!show" class="m-auto  justify-center flex gap-3 flex-col  items-center ">

                <h4
                    class="font-medium p-2 px-3 rounded-full font-semibold bg-gray-50 dark:bg-gray-800 dark:text-white dark:font-normal">
                    Select a conversation to start messaging</h4>
  
            </div>



      </main>
  </div>
   




</div>

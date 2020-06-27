import React, { lazy, useEffect, useState } from 'react';
import { ServerContext } from '@/state/server';
import getFileContents from '@/api/server/files/getFileContents';
import useRouter from 'use-react-router';
import { Actions, useStoreActions } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import { httpErrorToHuman } from '@/api/http';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import saveFileContents from '@/api/server/files/saveFileContents';
import FileManagerBreadcrumbs from '@/components/server/files/FileManagerBreadcrumbs';
import { useParams } from 'react-router';
import FileNameModal from '@/components/server/files/FileNameModal';
import Can from '@/components/elements/Can';
import FlashMessageRender from '@/components/FlashMessageRender';
import PageContentBlock from '@/components/elements/PageContentBlock';
import ServerError from '@/components/screens/ServerError';

const LazyAceEditor = lazy(() => import(/* webpackChunkName: "editor" */'@/components/elements/AceEditor'));

export default () => {
    const [ error, setError ] = useState('');
    const { action } = useParams();
    const { history, location: { hash } } = useRouter();
    const [ loading, setLoading ] = useState(action === 'edit');
    const [ content, setContent ] = useState('');
    const [ modalVisible, setModalVisible ] = useState(false);

    const { id, uuid } = ServerContext.useStoreState(state => state.server.data!);
    const { addError, clearFlashes } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);

    let fetchFileContent: null | (() => Promise<string>) = null;

    if (action !== 'new') {
        useEffect(() => {
            setLoading(true);
            setError('');
            getFileContents(uuid, hash.replace(/^#/, ''))
                .then(setContent)
                .catch(error => {
                    console.error(error);
                    setError(httpErrorToHuman(error));
                })
                .then(() => setLoading(false));
        }, [ uuid, hash ]);
    }

    const save = (name?: string) => {
        if (!fetchFileContent) {
            return;
        }

        setLoading(true);
        clearFlashes('files:view');
        fetchFileContent()
            .then(content => {
                return saveFileContents(uuid, name || hash.replace(/^#/, ''), content);
            })
            .then(() => {
                if (name) {
                    history.push(`/server/${id}/files/edit#/${name}`);
                    return;
                }

                return Promise.resolve();
            })
            .catch(error => {
                console.error(error);
                addError({ message: httpErrorToHuman(error), key: 'files:view' });
            })
            .then(() => setLoading(false));
    };

    if (error) {
        return (
            <ServerError
                message={error}
                onBack={() => history.goBack()}
            />
        );
    }

    return (
        <PageContentBlock>
            <FlashMessageRender byKey={'files:view'} className={'mb-4'}/>
            <FileManagerBreadcrumbs withinFileEditor={true} isNewFile={action !== 'edit'}/>
            {(name || hash.replace(/^#/, '')).endsWith('.pteroignore') &&
            <div className={'mb-4 p-4 border-l-4 bg-neutral-900 rounded border-cyan-400'}>
                <p className={'text-neutral-300 text-sm'}>
                    You're editing a <code className={'font-mono bg-black rounded py-px px-1'}>.pteroignore</code> file.
                    Any files or directories listed in here will be excluded from backups. Wildcards are supported by
                    using an asterisk (<code className={'font-mono bg-black rounded py-px px-1'}>*</code>). You can
                    negate a prior rule by prepending an exclamation point
                    (<code className={'font-mono bg-black rounded py-px px-1'}>!</code>).
                </p>
            </div>
            }
            <FileNameModal
                visible={modalVisible}
                onDismissed={() => setModalVisible(false)}
                onFileNamed={(name) => {
                    setModalVisible(false);
                    save(name);
                }}
            />
            <div className={'relative'}>
                <SpinnerOverlay visible={loading}/>
                <LazyAceEditor
                    initialModePath={hash.replace(/^#/, '') || 'plain_text'}
                    initialContent={content}
                    fetchContent={value => {
                        fetchFileContent = value;
                    }}
                    onContentSaved={() => null}
                />
            </div>
            <div className={'flex justify-end mt-4'}>
                {action === 'edit' ?
                    <Can action={'file.update'}>
                        <button className={'btn btn-primary btn-sm'} onClick={() => save()}>
                            Save Content
                        </button>
                    </Can>
                    :
                    <Can action={'file.create'}>
                        <button className={'btn btn-primary btn-sm'} onClick={() => setModalVisible(true)}>
                            Create File
                        </button>
                    </Can>
                }
            </div>
        </PageContentBlock>
    );
};

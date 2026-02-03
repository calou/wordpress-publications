import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, Spinner } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { FormTokenField } from '@wordpress/components';

export default function Edit({ attributes, setAttributes }) {
    const { selectedTags } = attributes;

    // Fetch all available tags
    const { tags, isLoading } = useSelect((select) => {
        const { getEntityRecords, isResolving } = select('core');
        return {
            tags: getEntityRecords('taxonomy', 'post_tag', { per_page: -1 }),
            isLoading: isResolving('getEntityRecords', ['taxonomy', 'post_tag', { per_page: -1 }]),
        };
    }, []);

    // Convert tag IDs to tag names for display
    const selectedTagNames = selectedTags
        ? selectedTags.map((tagId) => {
            const tag = tags?.find((t) => t.id === tagId);
            return tag ? tag.name : '';
        }).filter(Boolean)
        : [];

    // Get all tag names for suggestions
    const tagNames = tags ? tags.map((tag) => tag.name) : [];

    // Handle tag selection changes
    const onTagsChange = (newTagNames) => {
        const newTagIds = newTagNames
            .map((name) => {
                const tag = tags?.find((t) => t.name.toLowerCase() === name.toLowerCase());
                return tag ? tag.id : null;
            })
            .filter(Boolean);

        setAttributes({ selectedTags: newTagIds });
    };

    return (
        <>
            <InspectorControls>
                <PanelBody title="Tag Settings">
                    {isLoading ? (
                        <Spinner />
                    ) : (
                        <FormTokenField
                            label="Select Tags"
                            value={selectedTagNames}
                            suggestions={tagNames}
                            onChange={onTagsChange}
                            __experimentalExpandOnFocus
                            __experimentalShowHowTo={false}
                        />
                    )}
                </PanelBody>
            </InspectorControls>

            <div {...useBlockProps()}>
                {selectedTags && selectedTags.length > 0 ? (
                    <p>Selected tags: {selectedTagNames.join(', ')}</p>
                ) : (
                    <p>No tags selected. Use the sidebar to select tags.</p>
                )}
            </div>
        </>
    );
}
